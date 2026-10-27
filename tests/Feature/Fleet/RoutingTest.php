<?php

namespace Tests\Feature\Fleet;

class RoutingTest extends FleetTestCase
{
    public function test_fleet_subdomain_serves_the_react_apps(): void
    {
        $this->withoutVite();

        $this->get($this->fleetUrl('/'))->assertOk()->assertSee('<div id="root">', false)->assertSee('KOH Fleet');
        $this->get($this->fleetUrl('/login'))->assertOk();
        $this->get($this->fleetUrl('/admin'))->assertOk()->assertSee('KOH Fleet Admin');
        $this->get($this->fleetUrl('/admin/vehicles/12'))->assertOk()->assertSee('KOH Fleet Admin');
    }

    public function test_campaign_site_pages_do_not_answer_on_the_fleet_subdomain(): void
    {
        $this->withoutVite();

        // A GET is the beneficiary app (React shows its own not-found), never the campaign page
        $this->get($this->fleetUrl('/news'))->assertOk()->assertDontSee('data-page', false);
        $this->post($this->fleetUrl('/contact'))->assertNotFound();
        $this->post($this->fleetUrl('/admin/login'))->assertNotFound();
    }

    public function test_unknown_api_paths_return_json_404(): void
    {
        $this->get($this->fleetUrl('api/v1/nope'))->assertNotFound()->assertExactJson(['message' => 'Not found.']);
    }

    public function test_fleet_api_is_not_reachable_on_the_main_domain(): void
    {
        $this->postJson('http://localhost/api/v1/auth/request-code', ['phone' => '08030000001'])->assertNotFound();
    }

    public function test_fleet_uses_its_own_session_cookie(): void
    {
        $this->withoutVite();

        $siteCookie = config('session.cookie');

        $response = $this->get($this->fleetUrl('/'));

        $response->assertCookie(config('fleet.session_cookie'));
        $response->assertCookieMissing($siteCookie);
    }
}
