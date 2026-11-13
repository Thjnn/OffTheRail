<?php

namespace Tests\Feature;

use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_uses_settings(): void
    {
        app(ShopSettings::class)->set('name', 'Tiệm Áo Hà');
        app(ShopSettings::class)->set('zalo_url', 'https://zalo.me/0900000000');

        $this->get('/')->assertOk()
            ->assertSee('Tiệm Áo Hà')
            ->assertSee('https://zalo.me/0900000000', false)
            ->assertSee('FREESHIP ĐƠN TỪ 500K');
    }

    public function test_empty_contact_settings_hide_buttons(): void
    {
        $this->get('/')->assertOk()
            ->assertDontSee('zalo.me', false)
            ->assertDontSee('tel:', false);
    }

    public function test_accent_colour_comes_from_settings(): void
    {
        app(ShopSettings::class)->set('accent', '#AA3355');

        $this->get('/')->assertOk()->assertSee('--color-accent: #AA3355', false);
    }
}
