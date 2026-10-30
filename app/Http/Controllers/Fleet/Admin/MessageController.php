<?php

namespace App\Http\Controllers\Fleet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fleet\AuditLog;
use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\MessageTemplate;
use App\Models\Fleet\Notification;
use App\Services\Fleet\FleetNotifier;
use App\Services\Fleet\MessageTemplates;
use App\Services\Fleet\WhatsAppClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * WhatsApp messages: the log of everything sent, the templates (super admins edit them, and
 * Meta approves them), and messages admins send to one beneficiary or many.
 */
class MessageController extends Controller
{
    private const NAME_RULE = ['required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/'];

    private const LANGUAGE_RULE = ['required', 'string', 'regex:/^[a-z]{2,3}(_[A-Z]{2})?$/'];

    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search'));

        $notifications = Notification::query()
            ->with('beneficiary', 'messageTemplate', 'sender')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->query('beneficiary_id'), fn ($q, $id) => $q->where('beneficiary_id', $id))
            ->when($search !== '', fn ($q) => $q->whereHas('beneficiary', fn ($b) => $b->where(fn ($w) => $w
                ->where('full_name', 'like', "%{$search}%")->orWhere('phone', 'like', '%'.ltrim($search, '+0').'%'))))
            ->latest('id')
            ->paginate(min((int) $request->query('per_page', 25), 100))
            ->through(fn (Notification $notification) => $notification->toApi());

        return response()->json($notifications);
    }

    public function templates(WhatsAppClient $whatsApp): JsonResponse
    {
        return response()->json([
            'data' => MessageTemplate::with('editor')->orderByRaw('`key` is null')->orderBy('id')->get()->map->toApi(),
            'test_mode' => $whatsApp->isFake(),
            'can_manage_templates' => $whatsApp->isFake() || (bool) config('fleet.whatsapp.business_account_id'),
        ]);
    }

    /**
     * A new custom template, submitted to Meta straight away.
     */
    public function storeTemplate(Request $request, MessageTemplates $templates): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'name' => [...self::NAME_RULE, Rule::unique('fleet_message_templates', 'name')],
            'language' => self::LANGUAGE_RULE,
            'category' => ['required', Rule::in(['UTILITY', 'MARKETING'])],
            ...$this->wordingRules($request),
        ], $this->messages());

        $variables = $this->variables($data);

        $template = MessageTemplate::make([
            'title' => $data['title'],
            'name' => $data['name'],
            'language' => $data['language'],
            'category' => $data['category'],
            'variables' => $variables,
            'status' => 'not_submitted',
            'updated_by' => auth('fleet_admin')->id(),
        ]);

        $this->meta(fn () => $templates->submit($template, $data['body'], $variables));
        AuditLog::record('message_template.created', $template, ['name' => $template->name]);

        return response()->json(['template' => $template->fresh('editor')->toApi()], 201);
    }

    /**
     * Settings that don't need Meta: on/off, title, reminder timing, and which approved template to use.
     */
    public function updateTemplate(Request $request, MessageTemplate $template, MessageTemplates $templates): JsonResponse
    {
        $data = $request->validate([
            'enabled' => ['sometimes', 'boolean'],
            'title' => ['sometimes', 'required', 'string', 'max:100'],
            'days_before' => [Rule::prohibitedIf($template->key !== 'evaluation_due'), 'sometimes', 'integer', 'min:1', 'max:13'],
            // Point at a template made in WhatsApp Manager under another name
            'name' => ['sometimes', ...self::NAME_RULE, Rule::unique('fleet_message_templates', 'name')->ignore($template->id)],
            'language' => ['sometimes', ...self::LANGUAGE_RULE],
        ], $this->messages());

        $template->fill(collect($data)->only(['enabled', 'title', 'name', 'language'])->all());
        if (isset($data['days_before'])) {
            $template->settings = ['days_before' => (int) $data['days_before']] + ($template->settings ?? []);
        }

        $relinked = $template->isDirty(['name', 'language']);
        if ($relinked) {
            $template->fill(['status' => 'not_checked', 'status_reason' => null]);
        }

        $template->updated_by = auth('fleet_admin')->id();
        $template->save();

        if ($relinked) {
            $this->meta(fn () => $templates->refresh($template));
        }

        AuditLog::record('message_template.updated', $template, ['fields' => array_keys($data)]);

        return response()->json(['template' => $template->fresh('editor')->toApi()]);
    }

    /**
     * New wording, sent to Meta for approval. The current wording is used until it's approved.
     */
    public function updateWording(Request $request, MessageTemplate $template, MessageTemplates $templates): JsonResponse
    {
        $data = $request->validate($this->wordingRules($request, $template), $this->messages());

        $variables = $template->isAutomatic() ? null : $this->variables($data);

        $template->updated_by = auth('fleet_admin')->id();
        $this->meta(fn () => $templates->submit($template, $data['body'], $variables));
        AuditLog::record('message_template.submitted', $template, ['name' => $template->draft_name ?? $template->name]);

        return response()->json(['template' => $template->fresh('editor')->toApi()]);
    }

    public function refreshTemplate(MessageTemplate $template, MessageTemplates $templates): JsonResponse
    {
        $this->meta(fn () => $templates->refresh($template));

        return response()->json(['template' => $template->fresh('editor')->toApi()]);
    }

    public function discardDraft(MessageTemplate $template, MessageTemplates $templates): JsonResponse
    {
        $templates->discardDraft($template);
        AuditLog::record('message_template.draft_discarded', $template);

        return response()->json(['template' => $template->fresh('editor')->toApi()]);
    }

    /**
     * Custom templates only. It stays in Meta; delete it in WhatsApp Manager if it's no longer wanted.
     */
    public function destroyTemplate(MessageTemplate $template): JsonResponse
    {
        abort_if($template->isAutomatic(), 422, "Automatic messages can't be deleted. Switch them off instead.");

        AuditLog::record('message_template.deleted', $template, ['name' => $template->name]);
        $template->delete();

        return response()->json(null, 204);
    }

    /**
     * How many beneficiaries a send would reach.
     */
    public function audience(Request $request): JsonResponse
    {
        $data = $request->validate($this->audienceRules());

        return response()->json(['count' => $this->recipients($data)->count()]);
    }

    public function send(Request $request, FleetNotifier $notifier): JsonResponse
    {
        $data = $request->validate([
            'template_id' => ['required', 'integer', Rule::exists('fleet_message_templates', 'id')->whereNull('key')],
            'values' => ['present', 'array'],
            'values.*' => ['required', 'string', 'max:500'],
            ...$this->audienceRules(),
        ], [
            'values.*.required' => 'Fill in every blank.',
            'template_id.exists' => 'Choose one of the custom messages.',
        ]);

        $template = MessageTemplate::findOrFail($data['template_id']);

        if (!$template->canSend()) {
            throw ValidationException::withMessages(['template_id' => $template->enabled
                ? 'Meta has not approved this message yet, so it cannot be sent.'
                : 'This message is switched off.']);
        }
        if (count($data['values']) !== count($template->variableList()) - 1) {
            throw ValidationException::withMessages(['values' => 'Fill in every blank.']);
        }

        $recipients = $this->recipients($data)->get();
        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages(['audience' => 'Nobody matches. Suspended beneficiaries are never messaged.']);
        }

        $admin = auth('fleet_admin')->user();
        $batch = (string) Str::uuid();
        $recipients->each(fn (Beneficiary $beneficiary) => $notifier->custom($template, $beneficiary, $data['values'], $batch, $admin));

        AuditLog::record('messages.sent', $template, [
            'batch' => $batch,
            'recipients' => $recipients->count(),
            'audience' => $data['audience'],
            'lga' => $data['lga'] ?? null,
        ]);

        return response()->json(['queued' => $recipients->count(), 'batch' => $batch], 201);
    }

    private function audienceRules(): array
    {
        return [
            'audience' => ['required', Rule::in(['beneficiary', 'with_vehicle', 'all'])],
            'beneficiary_id' => ['required_if:audience,beneficiary', 'nullable', 'integer'],
            'lga' => ['nullable', 'string', Rule::in(config('fleet.lgas'))],
        ];
    }

    /**
     * Active beneficiaries only: suspended and removed ones are never messaged.
     */
    private function recipients(array $data): Builder
    {
        return Beneficiary::query()
            ->whereNull('deactivated_at')
            ->when($data['audience'] === 'beneficiary', fn ($q) => $q->whereKey($data['beneficiary_id']))
            ->when($data['audience'] === 'with_vehicle', fn ($q) => $q->whereHas('assignments', fn ($a) => $a->where('status', 'active')))
            ->when($data['audience'] !== 'beneficiary' && !empty($data['lga']), fn ($q) => $q->where('lga', $data['lga']));
    }

    /**
     * Automatic templates have fixed blanks; custom ones list theirs after {{1}}, always the first name.
     */
    private function wordingRules(Request $request, ?MessageTemplate $template = null): array
    {
        $blanks = $template?->isAutomatic()
            ? count($template->variableList())
            : count(array_filter((array) $request->input('variables', []), 'is_array')) + 1;

        $body = ['required', 'string', 'max:1024', function (string $attribute, mixed $value, \Closure $fail) use ($blanks) {
            if (is_string($value) && $error = MessageTemplate::wordingError($value, $blanks)) {
                $fail($error);
            }
        }];

        return $template?->isAutomatic() ? ['body' => $body] : [
            'body' => $body,
            'variables' => ['present', 'array', 'max:5'],
            'variables.*.label' => ['required', 'string', 'max:50'],
            'variables.*.example' => ['required', 'string', 'max:200'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.regex' => 'Use lowercase letters, numbers and underscores only, e.g. fleet_meeting_notice.',
            'name.unique' => 'Another message already uses this template name.',
            'language.regex' => 'Use a WhatsApp language code such as en, en_GB or en_US.',
            'variables.*.label.required' => 'Name every blank.',
            'variables.*.example.required' => 'Meta needs an example for every blank.',
        ];
    }

    /**
     * @return array<int, array{label: string, example: string}>
     */
    private function variables(array $data): array
    {
        return array_map(
            fn ($variable) => ['label' => trim($variable['label']), 'example' => FleetNotifier::clean($variable['example'], 200)],
            array_values($data['variables'] ?? []),
        );
    }

    /**
     * Meta errors (not configured, refused, unreachable) go back to the admin as a message.
     */
    private function meta(callable $call): mixed
    {
        try {
            return $call();
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['meta' => $e->getMessage()]);
        } catch (\Illuminate\Http\Client\ConnectionException) {
            throw ValidationException::withMessages(['meta' => "Couldn't reach Meta. Try again in a minute."]);
        }
    }
}
