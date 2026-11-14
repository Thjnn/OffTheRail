<?php

namespace Tests;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test không phụ thuộc vào việc đã `npm run build` hay chưa.
        $this->withoutVite();
    }

    /** Một biến thể còn hàng của một sản phẩm đang hiển thị (dùng cho test giỏ hàng, đơn hàng). */
    protected function variant(array $attrs = [], array $product = []): ProductVariant
    {
        $p = Product::factory()->create($product);
        $color = ProductColor::factory()->for($p)->create();

        return ProductVariant::factory()->for($p)->for($color, 'color')->create($attrs);
    }

    /** Hai tỉnh mẫu: 01 (phường 00004) và 79 (phường 26734). */
    protected function locations(): void
    {
        \App\Models\Province::upsert([['code' => '01', 'name' => 'Thành phố Hà Nội'], ['code' => '79', 'name' => 'Thành phố Hồ Chí Minh']], ['code']);
        \App\Models\Ward::upsert([
            ['code' => '00004', 'province_code' => '01', 'name' => 'Phường Ba Đình'],
            ['code' => '26734', 'province_code' => '79', 'name' => 'Phường Sài Gòn'],
        ], ['code']);
    }

    /** Thông tin người nhận hợp lệ cho OrderService::placeWebOrder(). */
    protected function contact(array $overrides = []): array
    {
        $this->locations();

        return $overrides + [
            'name' => 'Nguyễn An', 'phone' => '0901234567', 'email' => null,
            'province_code' => '01', 'ward_code' => '00004', 'street' => '1 Tràng Tiền', 'note' => null,
        ];
    }

    /** Đặt một đơn web 1 món (COD) qua đúng luồng thật. */
    protected function placeOrder(array $contact = [], ?\App\Models\Customer $account = null): \App\Models\Order
    {
        return app(\App\Services\OrderService::class)
            ->placeWebOrder([$this->variant(['stock' => 5])->id => 1], $this->contact($contact), 'cod', $account);
    }
}
