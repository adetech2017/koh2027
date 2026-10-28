<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetAdmin;

class AdminManagementTest extends FleetTestCase
{
    public function test_a_super_admin_can_add_and_suspend_admins(): void
    {
        $this->actingAsFleetAdmin('super_admin');

        $id = $this->fleetApi('POST', 'admin/admins', ['name' => 'Rev Iewer', 'email' => 'rev@example.com', 'role' => 'reviewer', 'password' => 'a-long-password'])
            ->assertCreated()->json('admin.id');

        $this->fleetApi('PATCH', "admin/admins/{$id}", ['active' => false])->assertJsonPath('admin.active', false);
        $this->assertFalse(FleetAdmin::find($id)->isActive());
    }

    public function test_a_super_admin_cannot_demote_or_suspend_themselves(): void
    {
        $me = $this->actingAsFleetAdmin('super_admin');

        $this->fleetApi('PATCH', "admin/admins/{$me->id}", ['role' => 'reviewer'])->assertJsonValidationErrors('role');
        $this->fleetApi('PATCH', "admin/admins/{$me->id}", ['active' => false])->assertJsonValidationErrors('role');
        $this->fleetApi('PATCH', "admin/admins/{$me->id}", ['name' => 'New Name'])->assertOk();
    }

    public function test_only_super_admins_manage_admins(): void
    {
        $this->actingAsFleetAdmin('fleet_officer');

        $this->fleetApi('GET', 'admin/admins')->assertForbidden();
    }
}
