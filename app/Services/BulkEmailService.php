<?php

namespace App\Services;

use App\Mail\BulkCampaignMail;
use App\Models\NewsletterSubscriber;
use App\Models\Volunteer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class BulkEmailService
{
    public const AUDIENCES = ['subscribers', 'volunteers', 'everyone'];

    // Volunteers who have been accepted onto the team
    private const VOLUNTEER_STATUSES = ['approved', 'active'];

    /**
     * Recipients for an audience, one entry per email address (a subscriber who is also a
     * volunteer gets one copy). Subscribers carry their unsubscribe link.
     *
     * @return Collection<int, array{email: string, first_name: ?string, unsubscribe_url: ?string}>
     */
    public function recipients(string $audience): Collection
    {
        $subscribers = in_array($audience, ['subscribers', 'everyone'])
            ? NewsletterSubscriber::where('status', 'confirmed')->get(['email', 'name', 'token'])
                ->map(fn ($s) => [
                    'email' => $s->email,
                    'first_name' => $s->name ? strtok($s->name, ' ') : null,
                    'unsubscribe_url' => route('newsletter.unsubscribe', $s->token),
                ])
            : collect();

        $volunteers = in_array($audience, ['volunteers', 'everyone'])
            ? Volunteer::whereIn('status', self::VOLUNTEER_STATUSES)->get(['email', 'first_name'])
                ->map(fn ($v) => ['email' => $v->email, 'first_name' => $v->first_name, 'unsubscribe_url' => null])
            : collect();

        // Prefer the subscriber entry when someone is both, so they keep an unsubscribe link
        return $subscribers->concat($volunteers)
            ->unique(fn ($r) => strtolower(trim($r['email'])))
            ->values();
    }

    public function counts(): array
    {
        return collect(self::AUDIENCES)->mapWithKeys(fn ($audience) => [$audience => $this->recipients($audience)->count()])->all();
    }

    /**
     * Queues one email per recipient. Returns how many were queued.
     */
    public function send(string $audience, array $content): int
    {
        $recipients = $this->recipients($audience);

        foreach ($recipients as $recipient) {
            Mail::queue(new BulkCampaignMail($content, $recipient));
        }

        return $recipients->count();
    }

    /**
     * Sends a single copy immediately (not queued) so the admin can check it in their inbox.
     */
    public function sendTest(string $email, ?string $firstName, array $content): void
    {
        Mail::send(new BulkCampaignMail(
            [...$content, 'subject' => '[TEST] '.$content['subject']],
            ['email' => $email, 'first_name' => $firstName, 'unsubscribe_url' => null, 'is_test' => true],
        ));
    }
}
