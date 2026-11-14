<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Sau khi seed dữ liệu demo: mọi trang admin mở được, và nhân viên bị chặn đúng chỗ. */
class AdminRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_page_with_demo_data(): void
    {
        Storage::fake('public');
        $this->seed();

        $order = Order::where('channel', 'web')->firstOrFail()->code;
        $product = Product::firstOrFail()->id;
        $customer = Customer::firstOrFail()->id;

        // [url, chỉ chủ shop?]
        $pages = [
            ['/admin', false],
            ['/admin/don-hang', false],
            ["/admin/don-hang/{$order}", false],
            ["/admin/don-hang/{$order}/in", false],
            ['/admin/don-hang/shopee', false],
            ['/admin/san-pham', false],
            ['/admin/kho', false],
            ['/admin/kho?low=1', false],
            ['/admin/san-pham/tao', true],
            ["/admin/san-pham/{$product}/sua", true],
            ['/admin/khach-hang', true],
            ["/admin/khach-hang/{$customer}", true],
            ['/admin/nhan-vien', true],
            ['/admin/noi-dung', true],
            ['/admin/cai-dat', true],
            ['/admin/nhat-ky', true],
            ['/admin/don-hang/xuat-excel', true],
            ['/admin/kho/xuat-excel', true],
        ];

        $owner = User::firstWhere('email', 'owner@demo.test');
        $staff = User::firstWhere('email', 'nhanvien1@demo.test');

        foreach ($pages as [$url, $ownerOnly]) {
            $this->actingAs($owner, 'admin')->get($url)->assertOk();
            $this->actingAs($staff, 'admin')->get($url)->assertStatus($ownerOnly ? 403 : 200);
        }
    }
}
