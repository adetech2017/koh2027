<?php

namespace Tests\Unit;

use App\Support\Fleet\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FleetPhoneNumberTest extends TestCase
{
    #[DataProvider('valid')]
    public function test_it_normalises_nigerian_mobile_numbers(string $input): void
    {
        $this->assertSame('+2348031234567', PhoneNumber::normalize($input));
    }

    public static function valid(): array
    {
        return [['08031234567'], ['0803 123 4567'], ['803-123-4567'], ['2348031234567'], ['+234 803 123 4567'], ['+234 (0) 803 123 4567']];
    }

    #[DataProvider('invalid')]
    public function test_it_rejects_other_input(?string $input): void
    {
        $this->assertNull(PhoneNumber::normalize($input));
    }

    public static function invalid(): array
    {
        return [[null], [''], ['12345'], ['0603 123 4567'], ['080312345678'], ['+44 7700 900123']];
    }

    public function test_it_masks_the_middle_digits(): void
    {
        $this->assertSame('+234 803 *** 4567', PhoneNumber::mask('+2348031234567'));
    }
}
