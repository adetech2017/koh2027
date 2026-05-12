<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventRsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^[\+\d\s\-\(\)]{7,20}$/'],
            'lga' => ['nullable', 'string', 'max:100'],
        ];
    }
}
