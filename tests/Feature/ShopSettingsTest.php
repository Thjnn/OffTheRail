<?php

namespace Tests\Feature;

use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_falls_back_to_config_when_db_empty(): void
    {
        $this->assertSame('Off the Rail', shop('name'));
        $this->assertSame(500000, shop('free_shipping_threshold'));
        $this->assertSame('x', shop('khong_ton_tai', 'x'));
    }

    public function test_db_value_overrides_config_and_cache_is_refreshed(): void
    {
        shop('name'); // làm ấm cache
        app(ShopSettings::class)->set('name', 'Tiệm Áo Hà');
        $this->assertSame('Tiệm Áo Hà', shop('name'));
    }
}
