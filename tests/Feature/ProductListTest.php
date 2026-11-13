<?php

namespace Tests\Feature;

use App\Livewire\ProductList;
use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_filters_by_category_size_and_sorts(): void
    {
        $this->seed(CatalogSeeder::class);

        Livewire::test(ProductList::class)
            ->set('danhMuc', 'so-mi')
            ->assertSee('Reach Shirt')->assertDontSee('Fern Tee')
            ->set('danhMuc', '')->set('sapXep', 'gia-tang')
            ->assertSeeInOrder(['Blend In Tee', 'Holding On Crew']);
    }

    public function test_size_filter_excludes_out_of_stock_variants(): void
    {
        $this->seed(CatalogSeeder::class);

        // Scrawl Tee có XL = 0
        Livewire::test(ProductList::class)->set('size', 'XL')
            ->assertDontSee('Scrawl Tee')->assertSee('Fern Tee');
    }

    public function test_filters_by_price_range_colour_and_search(): void
    {
        $this->seed(CatalogSeeder::class);

        Livewire::test(ProductList::class)
            ->set('giaTu', 700000)->assertSee('Holding On Crew')->assertDontSee('Fern Tee')
            ->set('giaTu', null)->set('giaDen', 400000)->assertSee('Blend In Tee')->assertDontSee('Fern Tee')
            ->set('giaDen', null)->set('mau', 'Đất nung')->assertSee('Fern Tee')->assertDontSee('Scrawl Tee')
            ->set('mau', '')->set('q', 'scrawl')->assertSee('Scrawl Tee')->assertDontSee('Fern Tee');
    }

    public function test_garbage_query_params_fall_back_to_defaults(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/san-pham?giaTu=abc&size=ZZ&sapXep=hack')->assertOk()->assertSee('Fern Tee');
        $this->get('/san-pham?giaTu=-5&giaDen[]=1&size[]=M&q[]=x&mau[]=y&danhMuc[]=z')->assertOk()->assertSee('Fern Tee');
    }

    public function test_garbage_values_set_from_the_browser_are_ignored(): void
    {
        $this->seed(CatalogSeeder::class);

        Livewire::test(ProductList::class)
            ->set('sapXep', 'hack')->set('size', 'ZZ')->set('giaTu', 'abc')
            ->assertSee('Fern Tee');
    }

    public function test_category_route_preselects_category_and_404s_unknown(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/danh-muc/ao-ni')->assertOk()->assertSee('Holding On Crew')->assertDontSee('Fern Tee');
        $this->get('/danh-muc/khong-co')->assertNotFound();
    }

    public function test_card_without_image_renders_placeholder(): void
    {
        Product::factory()->create(['name' => 'Áo Trơn', 'is_active' => true, 'published_at' => now()]);

        $this->get('/san-pham')->assertOk()->assertSee('Áo Trơn')->assertSee('Hết hàng');
    }

    public function test_hidden_products_are_not_listed_and_empty_state_shows(): void
    {
        Product::factory()->create(['name' => 'Áo Ẩn', 'is_active' => false]);

        $this->get('/san-pham')->assertOk()->assertDontSee('Áo Ẩn')->assertSee('Chưa có sản phẩm phù hợp');
    }

    public function test_paginates_twelve_per_page(): void
    {
        Product::factory()->count(13)->create();

        Livewire::test(ProductList::class)->assertViewHas('products', fn ($p) => $p->count() === 12 && $p->total() === 13);
    }
}
