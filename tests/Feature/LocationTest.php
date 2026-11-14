<?php

namespace Tests\Feature;

use App\Models\Province;
use App\Models\Ward;
use App\Support\ShippingFee;
use App\Support\ShopSettings;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_two_level_units(): void
    {
        $this->seed(ProvinceSeeder::class);
        $this->seed(ProvinceSeeder::class); // chạy lại không lỗi, không nhân đôi

        $this->assertSame(34, Province::count());
        $this->assertGreaterThan(3000, Ward::count());
        $this->assertSame(0, Ward::whereNotIn('province_code', Province::pluck('code'))->count());
        $this->assertNotNull(Province::where('name', 'like', '%Hà Nội%')->first());
    }

    public function test_shipping_fee(): void
    {
        $this->assertSame(0, ShippingFee::for(500000, '01'));
        $this->assertSame(30000, ShippingFee::for(499000, '01'));
        $this->assertSame(30000, ShippingFee::for(499000, null));

        app(ShopSettings::class)->set('shipping_fees', ['79' => 20000, '01' => 'abc']);
        $this->assertSame(20000, ShippingFee::for(100000, '79'));
        $this->assertSame(30000, ShippingFee::for(100000, '01'));

        app(ShopSettings::class)->set('shipping_fees', 'hỏng');
        $this->assertSame(30000, ShippingFee::for(100000, '79'));
    }
}
