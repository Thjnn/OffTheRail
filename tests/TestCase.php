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
}
