<?php

namespace Tests\Feature\Fleet;

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
}
