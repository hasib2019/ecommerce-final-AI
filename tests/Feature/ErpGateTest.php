<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErpGateTest extends TestCase
{
    public function test_dhaka_sets_plain_gate_cookie_and_redirects_to_erp(): void
    {
        config(['services.erp.secret' => 'abc123', 'services.erp.url' => 'https://erp.example.com', 'services.erp.cookie_domain' => '.example.com']);

        $response = $this->get('/dhaka');

        $response->assertRedirect('https://erp.example.com');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $header = collect($response->headers->all('set-cookie'))->first(fn ($c) => str_starts_with($c, 'gnw_erp_gate='));
        $this->assertNotNull($header);
        $this->assertStringStartsWith('gnw_erp_gate=abc123;', $header); // not encrypted
        $this->assertStringContainsString('domain=.example.com', $header);
        $this->assertStringContainsString('secure', $header);
        $this->assertStringContainsString('httponly', $header);
    }

    public function test_dhaka_is_404_when_no_secret_configured(): void
    {
        config(['services.erp.secret' => null]);

        $this->get('/dhaka')->assertNotFound();
    }
}
