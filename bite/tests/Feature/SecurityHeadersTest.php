<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    protected function tearDown(): void
    {
        Vite::useHotFile(public_path('hot'));

        parent::tearDown();
    }

    public function test_content_security_policy_stays_same_origin_without_vite_hot_server(): void
    {
        Vite::useHotFile(storage_path('framework/testing/missing-vite-hot'));

        $response = $this->get('/up');

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com;", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;", $csp);
        $this->assertStringContainsString("font-src 'self' https://fonts.gstatic.com;", $csp);
        $this->assertStringNotContainsString('127.0.0.1:5173', $csp);
    }

    public function test_content_security_policy_allows_vite_hot_server_when_present(): void
    {
        $hotFile = storage_path('framework/testing/vite.hot');
        File::ensureDirectoryExists(dirname($hotFile));
        File::put($hotFile, 'http://127.0.0.1:5173');

        Vite::useHotFile($hotFile);

        $response = $this->get('/up');

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('script-src', $csp);
        $this->assertStringContainsString('style-src', $csp);
        $this->assertStringContainsString('font-src', $csp);
        $this->assertStringContainsString('connect-src', $csp);
        $this->assertStringContainsString('http://127.0.0.1:5173', $csp);
        $this->assertStringContainsString('ws://127.0.0.1:5173', $csp);
        $this->assertStringContainsString('https://fonts.googleapis.com', $csp);
        $this->assertStringContainsString('https://fonts.gstatic.com', $csp);

        File::delete($hotFile);
    }
}
