<?php

namespace App\Http\Controllers;

use App\Http\Requests\VolunteerRequest;
use App\Mail\VolunteerWelcomeMail;
use App\Models\Volunteer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class VolunteerController extends Controller
{
    public function store(VolunteerRequest $request): RedirectResponse
    {
        $volunteer = Volunteer::create([
            ...$request->validated(),
            'ip_address' => $request->ip(),
        ]);

        Mail::send(new VolunteerWelcomeMail($volunteer));

        return back()->with('success', 'Thank you for volunteering! We will be in touch shortly.');
    }
}
