<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\FleetAdmin;

class AdminAuthTest extends FleetTestCase
{
    public function test_an_admin_can_sign_in_and_out(): void
    {
        $admin = FleetAdmin::factory()->create(['email' => 'officer@example.com']);

        $this->fleetApi('POST', 'admin/auth/login', ['email' => 'officer@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('admin.email', 'officer@example.com')
            ->assertJsonMissingPath('admin.password');

        $this->assertAuthenticatedAs($admin, 'fleet_admin');
        $this->fleetApi('GET', 'admin/auth/me')->assertOk()->assertJsonPath('admin.role', 'fleet_officer');
        $this->assertDatabaseHas('fleet_audit_logs', ['action' => 'admin.login', 'actor_type' => 'fleet_admin', 'actor_id' => $admin->id]);

        $this->fleetApi('POST', 'admin/auth/logout')->assertOk();
        $this->assertGuest('fleet_admin');
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        FleetAdmin::factory()->create(['email' => 'officer@example.com']);

        $this->fleetApi('POST', 'admin/auth/login', ['email' => 'officer@example.com', 'password' => 'wrong'])
            ->assertJsonValidationErrors('email');
        $this->assertGuest('fleet_admin');
    }

    public function test_a_suspended_admin_cannot_sign_in(): void
    {
        FleetAdmin::factory()->deactivated()->create(['email' => 'officer@example.com']);

        $this->fleetApi('POST', 'admin/auth/login', ['email' => 'officer@example.com', 'password' => 'password'])
            ->assertJsonValidationErrors('email');
        $this->assertGuest('fleet_admin');
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        FleetAdmin::factory()->create(['email' => 'officer@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->fleetApi('POST', 'admin/auth/login', ['email' => 'officer@example.com', 'password' => 'wrong']);
        }

        $this->fleetApi('POST', 'admin/auth/login', ['email' => 'officer@example.com', 'password' => 'password'])->assertStatus(429);
    }

    public function test_a_beneficiary_cannot_use_admin_endpoints(): void
    {
        $this->actingAs(Beneficiary::factory()->create(), 'fleet_beneficiary');

        $this->fleetApi('GET', 'admin/auth/me')->assertUnauthorized();
    }

    public function test_the_create_admin_command_creates_a_super_admin(): void
    {
        $this->artisan('fleet:create-admin')
            ->expectsQuestion('Name', 'Ada Fleet')
            ->expectsQuestion('Email', 'ada@example.com')
            ->expectsQuestion('Password (at least 10 characters)', 'a-long-password')
            ->assertSuccessful();

        $this->assertDatabaseHas('fleet_admins', ['email' => 'ada@example.com', 'role' => 'super_admin']);
    }
}
