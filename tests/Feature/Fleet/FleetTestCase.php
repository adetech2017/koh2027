<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class FleetTestCase extends TestCase
{
    use RefreshDatabase;

    protected function fleetUrl(string $path = ''): string
    {
        return 'http://'.config('fleet.domain').'/'.ltrim($path, '/');
    }

    protected function fleetApi(string $method, string $path, array $data = [])
    {
        return $this->json($method, $this->fleetUrl('api/v1/'.ltrim($path, '/')), $data);
    }

    protected function actingAsFleetAdmin(string $role = 'fleet_officer'): FleetAdmin
    {
        $admin = FleetAdmin::factory()->role($role)->create();
        $this->actingAs($admin, 'fleet_admin');

        return $admin;
    }

    /**
     * A small real PNG as the signature pad sends it.
     */
    protected function signature(): string
    {
        $image = imagecreatetruecolor(40, 20);
        imageline($image, 2, 10, 38, 10, imagecolorallocate($image, 255, 255, 255));
        ob_start();
        imagepng($image);

        return 'data:image/png;base64,'.base64_encode(ob_get_clean());
    }

    protected function checklist(array $overrides = []): array
    {
        return array_merge(
            collect(config('fleet.handover_checklist'))->map(fn () => ['status' => 'ok', 'note' => null])->all(),
            $overrides,
        );
    }
}
