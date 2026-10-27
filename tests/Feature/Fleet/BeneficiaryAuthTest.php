<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\Otp;
use App\Services\Fleet\WhatsAppClient;
use Mockery;

class BeneficiaryAuthTest extends FleetTestCase
{
    private ?string $sentCode = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Capture the code instead of sending it
        $whatsApp = Mockery::mock(WhatsAppClient::class);
        $whatsApp->shouldReceive('sendOtp')->andReturnUsing(function ($phone, $code) {
            $this->sentCode = $code;
        });
        $this->app->instance(WhatsAppClient::class, $whatsApp);
    }

    public function test_a_registered_beneficiary_can_sign_in_with_a_whatsapp_code(): void
    {
        $beneficiary = Beneficiary::factory()->create(['phone' => '+2348031234567']);

        // Typed the local way; stored and messaged in E.164
        $this->fleetApi('POST', 'auth/request-code', ['phone' => '0803 123 4567'])
            ->assertOk()
            ->assertJson(['phone' => '+234 803 *** 4567']);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->sentCode);

        $this->fleetApi('POST', 'auth/verify', ['phone' => '+234 803 123 4567', 'code' => $this->sentCode])
            ->assertOk()
            ->assertJsonPath('beneficiary.id', $beneficiary->id);

        $this->assertAuthenticatedAs($beneficiary, 'fleet_beneficiary');
        $this->fleetApi('GET', 'auth/me')->assertOk()->assertJsonPath('beneficiary.full_name', $beneficiary->full_name);
        $this->assertNotNull($beneficiary->fresh()->last_login_at);
        $this->assertDatabaseHas('fleet_audit_logs', ['action' => 'beneficiary.login', 'actor_type' => 'fleet_beneficiary', 'actor_id' => $beneficiary->id]);
    }

    public function test_an_unregistered_number_gets_the_same_reply_but_no_code(): void
    {
        $this->fleetApi('POST', 'auth/request-code', ['phone' => '08039999999'])
            ->assertOk()
            ->assertJson(['message' => 'If this number is registered, a login code has been sent to it on WhatsApp.']);

        $this->assertNull($this->sentCode);
        $this->assertDatabaseCount('fleet_otps', 0);
    }

    public function test_a_suspended_beneficiary_gets_no_code(): void
    {
        Beneficiary::factory()->deactivated()->create(['phone' => '+2348031234567']);

        $this->fleetApi('POST', 'auth/request-code', ['phone' => '08031234567'])->assertOk();

        $this->assertNull($this->sentCode);
    }

    public function test_an_invalid_number_is_rejected(): void
    {
        $this->fleetApi('POST', 'auth/request-code', ['phone' => '12345'])->assertJsonValidationErrors('phone');
    }

    public function test_a_wrong_code_does_not_sign_in_and_the_code_dies_after_five_wrong_tries(): void
    {
        Beneficiary::factory()->create(['phone' => '+2348031234567']);
        $this->fleetApi('POST', 'auth/request-code', ['phone' => '08031234567']);
        $wrong = $this->sentCode === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->fleetApi('POST', 'auth/verify', ['phone' => '08031234567', 'code' => $wrong])->assertJsonValidationErrors('code');
        }

        // Even the right code no longer works
        $this->fleetApi('POST', 'auth/verify', ['phone' => '08031234567', 'code' => $this->sentCode])->assertJsonValidationErrors('code');
        $this->assertGuest('fleet_beneficiary');
    }

    public function test_an_expired_code_is_rejected(): void
    {
        Beneficiary::factory()->create(['phone' => '+2348031234567']);
        $this->fleetApi('POST', 'auth/request-code', ['phone' => '08031234567']);

        $this->travel(11)->minutes();

        $this->fleetApi('POST', 'auth/verify', ['phone' => '08031234567', 'code' => $this->sentCode])->assertJsonValidationErrors('code');
    }

    public function test_a_code_cannot_be_used_twice_and_a_new_code_replaces_the_old_one(): void
    {
        Beneficiary::factory()->create(['phone' => '+2348031234567']);

        $this->fleetApi('POST', 'auth/request-code', ['phone' => '08031234567']);
        $first = $this->sentCode;
        $this->travel(2)->minutes();
        $this->fleetApi('POST', 'auth/request-code', ['phone' => '08031234567']);

        $this->assertSame(1, Otp::whereNull('consumed_at')->count());
        if ($first !== $this->sentCode) {
            $this->fleetApi('POST', 'auth/verify', ['phone' => '08031234567', 'code' => $first])->assertJsonValidationErrors('code');
        }

        $this->fleetApi('POST', 'auth/verify', ['phone' => '08031234567', 'code' => $this->sentCode])->assertOk();
        $this->fleetApi('POST', 'auth/logout')->assertOk();
        $this->fleetApi('POST', 'auth/verify', ['phone' => '08031234567', 'code' => $this->sentCode])->assertJsonValidationErrors('code');
    }

    public function test_code_requests_are_rate_limited_per_number(): void
    {
        Beneficiary::factory()->create(['phone' => '+2348031234567']);

        $this->fleetApi('POST', 'auth/request-code', ['phone' => '08031234567'])->assertOk();
        // Same number written differently still counts
        $this->fleetApi('POST', 'auth/request-code', ['phone' => '+2348031234567'])->assertStatus(429);
    }

    public function test_guests_cannot_reach_protected_endpoints(): void
    {
        $this->fleetApi('GET', 'auth/me')->assertUnauthorized();
    }

    public function test_a_campaign_site_login_is_not_a_fleet_login(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)->fleetApi('GET', 'auth/me')->assertUnauthorized();
        $this->fleetApi('GET', 'admin/auth/me')->assertUnauthorized();
    }

    public function test_a_beneficiary_suspended_mid_session_is_signed_out(): void
    {
        $beneficiary = Beneficiary::factory()->create();
        $this->actingAs($beneficiary, 'fleet_beneficiary');

        $beneficiary->forceFill(['deactivated_at' => now()])->save();

        $this->fleetApi('GET', 'auth/me')->assertForbidden();
        $this->assertGuest('fleet_beneficiary');
    }
}
