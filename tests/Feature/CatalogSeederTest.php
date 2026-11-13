<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_seeds_demo_catalog(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->assertSame(3, Category::count());
        $this->assertSame(10, Product::count());

        $fern = Product::where('slug', 'fern-tee')->with('colors', 'variants', 'blocks')->first();
        $this->assertSame(440000, $fern->price);
        $this->assertCount(2, $fern->colors);
        $this->assertCount(10, $fern->variants);   // 2 màu × 5 size
        $this->assertCount(2, $fern->blocks);
        Storage::disk('public')->assertExists('products/fern-tee-front.webp');
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(CatalogSeeder::class);

        $this->assertSame(10, Product::count());
        $this->assertSame(100, ProductVariant::count());
        $this->assertSame(30, ProductImage::count());
    }

    public function test_upcoming_product_is_hidden_and_sale_price_converted(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->assertFalse(Product::where('slug', 'one-of-one-tee')->first()->isVisible());
        $blend = Product::where('slug', 'blend-in-tee')->first();
        $this->assertSame(380000, $blend->price);
        $this->assertSame(480000, $blend->compare_at_price);
    }
}
