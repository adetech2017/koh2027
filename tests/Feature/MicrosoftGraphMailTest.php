<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class MicrosoftGraphMailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.default' => 'microsoft-graph',
            'mail.from' => ['address' => 'noreply@koh2027.ng', 'name' => 'KOH 2027'],
            'mail.mailers.microsoft-graph.tenant_id' => 'tenant-123',
            'mail.mailers.microsoft-graph.client_id' => 'client-456',
            'mail.mailers.microsoft-graph.client_secret' => 'secret-789',
        ]);
    }

    private function fakeGraph(int $sendStatus = 202): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'token-abc', 'expires_in' => 3599]),
            'graph.microsoft.com/*' => $sendStatus === 202
                ? Http::response(null, 202)
                : Http::response(['error' => ['code' => 'ErrorAccessDenied', 'message' => 'Access is denied.']], $sendStatus),
        ]);
    }

    private function sentMime(): string
    {
        $request = Http::recorded(fn (Request $request) => str_contains($request->url(), 'sendMail'))->first()[0];

        return base64_decode($request->body());
    }

    public function test_mail_is_sent_from_the_mailbox_as_mime_with_an_app_token(): void
    {
        $this->fakeGraph();

        Mail::raw('Hello from the campaign', fn ($m) => $m->to('voter@example.com')->subject('Graph test'));

        Http::assertSent(fn (Request $request) => $request->url() === 'https://login.microsoftonline.com/tenant-123/oauth2/v2.0/token'
            && $request['client_id'] === 'client-456'
            && $request['client_secret'] === 'secret-789'
            && $request['grant_type'] === 'client_credentials');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://graph.microsoft.com/v1.0/users/noreply%40koh2027.ng/sendMail'
            && $request->hasHeader('Authorization', 'Bearer token-abc')
            && $request->hasHeader('Content-Type', 'text/plain'));

        $mime = $this->sentMime();
        $this->assertStringContainsString('Subject: Graph test', $mime);
        $this->assertStringContainsString('To: voter@example.com', $mime);
        $this->assertStringContainsString('Hello from the campaign', $mime);
    }

    public function test_the_token_is_reused_between_messages(): void
    {
        $this->fakeGraph();

        Mail::raw('One', fn ($m) => $m->to('a@example.com')->subject('One'));
        Mail::raw('Two', fn ($m) => $m->to('b@example.com')->subject('Two'));

        $this->assertCount(1, Http::recorded(fn (Request $request) => str_contains($request->url(), 'login.microsoftonline.com')));
        $this->assertCount(2, Http::recorded(fn (Request $request) => str_contains($request->url(), 'sendMail')));
    }

    public function test_blind_copies_reach_graph(): void
    {
        $this->fakeGraph();

        Mail::raw('Hi', fn ($m) => $m->to('a@example.com')->bcc('team@koh2027.ng')->subject('Bcc'));

        $this->assertMatchesRegularExpression('/^Bcc: team@koh2027\.ng\r\n/m', $this->sentMime());
    }

    public function test_a_refusal_from_graph_is_reported(): void
    {
        $this->fakeGraph(403);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Microsoft Graph refused the message from noreply@koh2027.ng (HTTP 403): Access is denied.');

        Mail::raw('Hi', fn ($m) => $m->to('a@example.com')->subject('Denied'));
    }
}
