<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Shift;
use App\Models\User;
use App\Support\SalesReport;
use Database\Seeders\DemoCustomerSeeder;
use Database\Seeders\DemoOrderSeeder;
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

    public function test_demo_staff_and_orders_for_admin(): void
    {
        Storage::fake('public');
        $this->seed();
        $count = Order::count();
        $this->seed(DemoOrderSeeder::class); // chạy lại không tạo thêm

        $this->assertSame($count, Order::count());
        $this->assertGreaterThanOrEqual(150, $count);
        $this->assertSame(1, User::where('role', 'owner')->count());
        $this->assertSame(2, User::where('role', 'staff')->count());

        foreach (['owner@demo.test', 'nhanvien1@demo.test', 'nhanvien2@demo.test'] as $email) {
            $this->assertTrue(Hash::check('matkhau123', User::firstWhere('email', $email)->password), $email);
        }

        $daily = (new SalesReport(now()->subDays(29), now()))->daily();
        $this->assertGreaterThan(0, array_sum($daily['series']['web']));
        $this->assertGreaterThan(0, array_sum($daily['series']['shopee']));
        $this->assertTrue(Order::where('created_at', '<', now()->subDays(50))->exists());
        $this->assertTrue(Order::where('status', 'pending')->where('channel', 'web')->exists());
        $this->assertGreaterThan(0, ProductVariant::lowStock()->count());
        $this->assertSame(0, ProductVariant::where('stock', '<', 0)->count());

        // Bán tại quầy: đơn nào cũng thuộc một ca, vài ca đã chốt, tối đa một ca đang mở.
        $this->assertGreaterThan(0, array_sum($daily['series']['pos']));
        $this->assertGreaterThan(20, Order::where('channel', 'pos')->count());
        $this->assertSame(0, Order::where('channel', 'pos')->whereNull('shift_id')->count());
        $this->assertGreaterThan(3, Shift::whereNotNull('closed_at')->count());
        $this->assertLessThanOrEqual(1, Shift::open()->count());
        $this->assertTrue(Shift::whereNotNull('closed_at')->whereColumn('counted_cash', '!=', 'expected_cash')->exists());
    }
}
