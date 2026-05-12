<?php

namespace App\Services;

use App\Mail\BulkCampaignMail;
use App\Models\NewsletterSubscriber;
use App\Models\Volunteer;
use Illuminate\Support\Facades\Mail;

class BulkEmailService
{
    public function sendToSubscribers(string $subject, string $body, string $status = 'confirmed'): int
    {
        $subscribers = NewsletterSubscriber::where('status', $status)->pluck('email');
        $count = 0;

        foreach ($subscribers->chunk(50) as $batch) {
            foreach ($batch as $email) {
                Mail::queue(new BulkCampaignMail($subject, $body, $email));
                $count++;
            }
        }

        return $count;
    }

    public function sendToVolunteers(string $subject, string $body, array $statuses = ['active']): int
    {
        $volunteers = Volunteer::whereIn('status', $statuses)->pluck('email');
        $count = 0;

        foreach ($volunteers->chunk(50) as $batch) {
            foreach ($batch as $email) {
                Mail::queue(new BulkCampaignMail($subject, $body, $email));
                $count++;
            }
        }

        return $count;
    }
}
