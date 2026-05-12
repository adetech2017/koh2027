<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\EventRsvp;
use App\Models\NewsletterSubscriber;
use App\Models\Volunteer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function exportContacts(array $filters = []): StreamedResponse
    {
        $query = Contact::query();

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        $contacts = $query->cursor();

        return $this->streamCsv('contacts.csv', ['Name', 'Email', 'Phone', 'Subject', 'Status', 'Date'], $this->contactsToRows($contacts));
    }

    public function exportVolunteers(array $filters = []): StreamedResponse
    {
        $query = Volunteer::query();

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['lga']) && $filters['lga']) {
            $query->where('lga', $filters['lga']);
        }

        $volunteers = $query->cursor();

        return $this->streamCsv('volunteers.csv', ['Name', 'Email', 'Phone', 'LGA', 'Skills', 'Status', 'Date'], $this->volunteersToRows($volunteers));
    }

    public function exportSubscribers(array $filters = []): StreamedResponse
    {
        $query = NewsletterSubscriber::query();

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        $subscribers = $query->cursor();

        return $this->streamCsv('subscribers.csv', ['Email', 'Name', 'Status', 'Date'], $this->subscribersToRows($subscribers));
    }

    public function exportRsvps(int $eventId): StreamedResponse
    {
        $rsvps = EventRsvp::where('event_id', $eventId)->cursor();

        return $this->streamCsv('rsvps.csv', ['Name', 'Email', 'Phone', 'LGA', 'Status', 'Date'], $this->rsvpsToRows($rsvps));
    }

    private function streamCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function contactsToRows(iterable $contacts): iterable
    {
        foreach ($contacts as $contact) {
            yield [
                $contact->name,
                $contact->email,
                $contact->phone,
                $contact->subject,
                $contact->status,
                $contact->created_at->format('Y-m-d H:i'),
            ];
        }
    }

    private function volunteersToRows(iterable $volunteers): iterable
    {
        foreach ($volunteers as $volunteer) {
            yield [
                $volunteer->full_name,
                $volunteer->email,
                $volunteer->phone,
                $volunteer->lga,
                implode(', ', $volunteer->skills ?? []),
                $volunteer->status,
                $volunteer->created_at->format('Y-m-d H:i'),
            ];
        }
    }

    private function subscribersToRows(iterable $subscribers): iterable
    {
        foreach ($subscribers as $subscriber) {
            yield [
                $subscriber->email,
                $subscriber->name,
                $subscriber->status,
                $subscriber->created_at->format('Y-m-d H:i'),
            ];
        }
    }

    private function rsvpsToRows(iterable $rsvps): iterable
    {
        foreach ($rsvps as $rsvp) {
            yield [
                $rsvp->name,
                $rsvp->email,
                $rsvp->phone,
                $rsvp->lga,
                $rsvp->status,
                $rsvp->created_at->format('Y-m-d H:i'),
            ];
        }
    }
}
