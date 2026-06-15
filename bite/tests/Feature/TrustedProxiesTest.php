<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/__test-ip', fn () => request()->ip())
            ->middleware('web');
    }

    public function test_spoofed_x_forwarded_for_from_untrusted_proxy_is_rejected(): void
    {
        // A public client (203.0.113.10) connects directly and tries to spoof
        // its IP via X-Forwarded-For. Because it is NOT a trusted proxy, the
        // header must be ignored and the real connecting IP returned.
        $response = $this->call('GET', '/__test-ip', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
        ]);

        $response->assertOk();
        $this->assertSame(
            '203.0.113.10',
            $response->getContent(),
            'Untrusted proxy must not be able to spoof request()->ip() via X-Forwarded-For',
        );
    }

    public function test_local_same_host_proxy_x_forwarded_for_is_honored(): void
    {
        // The real deployment: same-host nginx (127.0.0.1) forwards the genuine
        // client IP via X-Forwarded-For. Loopback is trusted, so the forwarded
        // client IP must be returned.
        $response = $this->call('GET', '/__test-ip', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
        ]);

        $response->assertOk();
        $this->assertSame(
            '1.2.3.4',
            $response->getContent(),
            'Trusted same-host nginx proxy must be able to forward the real client IP',
        );
    }
}
