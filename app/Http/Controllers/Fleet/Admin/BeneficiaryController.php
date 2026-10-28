<?php

namespace App\Http\Controllers\Fleet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Assignment;
use App\Models\Fleet\AuditLog;
use App\Models\Fleet\Beneficiary;
use App\Support\Fleet\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BeneficiaryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search'));

        $beneficiaries = Beneficiary::query()
            ->with('currentAssignment.vehicle')
            ->when($search !== '', function ($query) use ($search) {
                // Partial numbers too: "0803 12" matches +23480312...
                $digits = ltrim(preg_replace('/\D+/', '', $search), '0');
                $query->where(fn ($q) => $q
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('id_number', $search)
                    ->when(strlen($digits) >= 3, fn ($q) => $q->orWhere('phone', 'like', "%{$digits}%")));
            })
            ->when($request->query('lga'), fn ($q, $lga) => $q->where('lga', $lga))
            ->when($request->query('status') === 'suspended', fn ($q) => $q->whereNotNull('deactivated_at'))
            ->when($request->query('status') === 'active', fn ($q) => $q->whereNull('deactivated_at'))
            ->when($request->query('vehicle') === 'with', fn ($q) => $q->whereHas('currentAssignment'))
            ->when($request->query('vehicle') === 'without', fn ($q) => $q->whereDoesntHave('currentAssignment'))
            ->orderBy('full_name')
            ->paginate(min((int) $request->query('per_page', 25), 100))
            ->through(fn (Beneficiary $beneficiary) => $beneficiary->toAdminApi() + [
                'vehicle' => $beneficiary->currentAssignment?->vehicle?->toApi(),
            ]);

        return response()->json($beneficiaries);
    }

    public function show(Beneficiary $beneficiary): JsonResponse
    {
        $assignments = $beneficiary->assignments()
            ->with('vehicle', 'recall', 'handoverForm', 'returnForm')
            ->latest('handed_over_at')
            ->get()
            ->map(fn (Assignment $assignment) => $assignment->toApi());

        return response()->json([
            'beneficiary' => $beneficiary->toAdminApi(),
            'assignments' => $assignments,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->normalisePhones($request);
        $data = $request->validate(static::rules(), static::messages());

        $beneficiary = Beneficiary::create($data);
        $beneficiary->forceFill(['created_by' => auth('fleet_admin')->id()])->save();
        AuditLog::record('beneficiary.created', $beneficiary);

        return response()->json(['beneficiary' => $beneficiary->toAdminApi()], 201);
    }

    public function update(Request $request, Beneficiary $beneficiary): JsonResponse
    {
        $this->normalisePhones($request);
        $data = $request->validate(static::partial(static::rules($beneficiary)) + [
            'active' => ['sometimes', 'boolean'],
        ], static::messages());

        $beneficiary->fill(collect($data)->except('active')->all());
        if (array_key_exists('active', $data)) {
            $beneficiary->deactivated_at = $data['active'] ? null : ($beneficiary->deactivated_at ?? now());
        }
        $beneficiary->save();
        AuditLog::record('beneficiary.updated', $beneficiary, ['fields' => array_keys($data)]);

        return response()->json(['beneficiary' => $beneficiary->toAdminApi()]);
    }

    /**
     * PATCH sends only the fields being changed, so each rule applies only when its field is present.
     */
    private static function partial(array $rules): array
    {
        return array_map(fn (array $fieldRules) => ['sometimes', ...$fieldRules], $rules);
    }

    /**
     * Shared with the CSV import. Phone numbers must already be normalised.
     */
    public static function rules(?Beneficiary $ignore = null): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required', 'string', 'regex:/^\+234[789]\d{9}$/',
                Rule::unique('fleet_beneficiaries', 'phone')->ignore($ignore?->id),
            ],
            'alt_phone' => ['nullable', 'string', 'regex:/^\+234[789]\d{9}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'lga' => ['nullable', Rule::in(config('fleet.lgas'))],
            'ward' => ['nullable', 'string', 'max:100'],
            'id_type' => ['nullable', Rule::in(array_keys(config('fleet.id_types')))],
            'id_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public static function messages(): array
    {
        $invalid = 'Enter a valid Nigerian mobile number, e.g. 0803 123 4567.';

        return [
            'phone.regex' => $invalid,
            'alt_phone.regex' => $invalid,
            'phone.unique' => 'Another beneficiary already has this phone number.',
        ];
    }

    /**
     * 0803... → +234803... on the request itself, so numbers validate and store in one form.
     * Unparseable numbers are left as typed for validation to report.
     */
    private function normalisePhones(Request $request): void
    {
        foreach (['phone', 'alt_phone'] as $field) {
            if ($request->filled($field)) {
                $request->merge([$field => PhoneNumber::normalize($request->input($field)) ?? $request->input($field)]);
            }
        }
    }
}
