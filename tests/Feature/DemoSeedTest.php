<?php

namespace Tests\Feature;

use App\Models\Customer;
use Database\Seeders\DemoCustomerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_seed_gives_working_demo(): void
    {
        Storage::fake('public');
        $this->seed();
        $this->seed(DemoCustomerSeeder::class); // chạy lại không tạo trùng

        $c = Customer::where('phone', '0900000001')->first();
        $this->assertTrue(Hash::check('matkhau123', $c->password));
        $this->assertSame(2, $c->visibleOrders()->count());
        $this->assertSame(1, $c->addresses()->count());
        $this->assertSame(2, $c->wishlist()->count());

        foreach (['/', '/san-pham', '/gio-hang', '/tra-cuu-don', '/tai-khoan/dang-nhap', '/tai-khoan/dang-ky', '/tai-khoan/quen-mat-khau'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->actingAs($c);
        foreach (['/tai-khoan', '/tai-khoan/dia-chi', '/tai-khoan/yeu-thich'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
