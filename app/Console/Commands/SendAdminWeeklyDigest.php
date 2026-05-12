<?php

namespace App\Console\Commands;

use App\Mail\AdminWeeklyDigestMail;
use App\Models\Contact;
use App\Models\EventRsvp;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAdminWeeklyDigest extends Command
{
    protected $signature = 'crm:weekly-digest';
    protected $description = 'Send weekly digest email to admins';

    public function handle(): int
    {
        $sevenDaysAgo = now()->subDays(7);

        $newContacts = Contact::where('created_at', '>=', $sevenDaysAgo)->count();
        $newVolunteers = Volunteer::where('created_at', '>=', $sevenDaysAgo)->count();
        $newRsvps = EventRsvp::where('created_at', '>=', $sevenDaysAgo)->count();
        $newSubscribers = NewsletterSubscriber::where('created_at', '>=', $sevenDaysAgo)->count();

        $admins = User::where('role', 'admin')->get(['email']);

        foreach ($admins as $admin) {
            Mail::to($admin->email)->queue(
                new AdminWeeklyDigestMail($newContacts, $newVolunteers, $newRsvps, $newSubscribers)
            );
        }

        $this->info("Weekly digest sent to {$admins->count()} admins");

        return self::SUCCESS;
    }
}
