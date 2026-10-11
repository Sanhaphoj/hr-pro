<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_manifest_is_public_and_installable(): void
    {
        $this->get(route('manifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('start_url', '/?source=pwa')
            ->assertJsonPath('icons.1.sizes', '512x512')
            ->assertJsonPath('icons.2.purpose', 'maskable')
            ->assertJsonPath('shortcuts.0.url', '/time-clock');
    }

    public function test_the_sign_in_page_links_the_manifest(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<link rel="manifest" href="'.route('manifest').'">', false);
    }

    public function test_the_icons_and_offline_page_ship_with_the_app(): void
    {
        foreach (['icons/icon-192.png', 'icons/icon-512.png', 'icons/icon-maskable-512.png', 'icons/apple-touch-icon.png', 'offline.html', 'sw.js'] as $file) {
            $this->assertFileExists(public_path($file));
        }
    }
}
