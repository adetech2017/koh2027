<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VolunteerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:60'],
            'last_name' => ['required', 'string', 'min:2', 'max:60'],
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:volunteers,email'],
            'phone' => ['required', 'string', 'regex:/^[\+\d\s\-\(\)]{7,20}$/'],
            'lga' => ['required', 'string', 'max:100'],
            'ward' => ['nullable', 'string', 'max:100'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'motivation' => ['nullable', 'string', 'max:1000'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'in:social_media,canvassing,data_entry,driving,photography,event_management,public_speaking,fundraising'],
            'has_vehicle' => ['boolean'],
        ];
    }
}
