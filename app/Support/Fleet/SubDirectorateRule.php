<?php

namespace App\Support\Fleet;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A sub-directorate must be one listed under the chosen directorate in config('fleet.directorates').
 * On a partial update the directorate may not be in the request, so the saved one is the fallback.
 */
class SubDirectorateRule implements DataAwareRule, ValidationRule
{
    private array $data = [];

    public function __construct(private ?string $savedDirectorate = null) {}

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $directorate = array_key_exists('directorate', $this->data) ? $this->data['directorate'] : $this->savedDirectorate;
        $options = config('fleet.directorates')[$directorate] ?? [];

        if (!$directorate) {
            $fail('Choose a directorate first.');
        } elseif ($options === []) {
            $fail("{$directorate} has no sub-directorates.");
        } elseif (!in_array($value, $options, true)) {
            $fail("Choose a sub-directorate of {$directorate}.");
        }
    }
}
