<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_visible_scope_hides_inactive_and_future(): void
    {
        $ok = Product::factory()->create(['is_active' => true, 'published_at' => now()->subDay()]);
        Product::factory()->create(['is_active' => false]);
        Product::factory()->create(['is_active' => true, 'published_at' => now()->addDay()]);
        $this->assertEquals([$ok->id], Product::visible()->pluck('id')->all());
    }

    public function test_stock_sale_and_new_helpers(): void
    {
        $p = Product::factory()->create(['price' => 280000, 'compare_at_price' => 350000, 'published_at' => now()->subDays(3)]);
        $c = ProductColor::factory()->for($p)->create();
        ProductVariant::factory()->for($p)->for($c, 'color')->create(['size' => 'M', 'stock' => 2]);
        ProductVariant::factory()->for($p)->for($c, 'color')->create(['size' => 'L', 'stock' => 0]);
        $p->load('variants');
        $this->assertSame(2, $p->totalStock());
        $this->assertTrue($p->isOnSale());
        $this->assertSame(20, $p->discountPercent());
        $this->assertTrue($p->isNew());
    }

    public function test_product_without_images_or_variants_is_safe(): void
    {
        $p = Product::factory()->create(['compare_at_price' => null]);
        $this->assertNull($p->coverImageUrl());
        $this->assertSame(0, $p->load('variants')->totalStock());
        $this->assertTrue($p->isSoldOut());
        $this->assertNull($p->discountPercent());
    }
}
