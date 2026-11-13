<?php

namespace Tests\Feature;

use App\Livewire\VariantPicker;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Support\ShopSettings;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_shows_product_with_og_tags(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/san-pham/fern-tee')->assertOk()
            ->assertSee('Fern Tee')->assertSee('440.000đ')
            ->assertSee('<meta property="og:title" content="Fern Tee"', false)
            ->assertSee('og:image', false);
    }

    public function test_shows_story_blocks_size_chart_and_related_products(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/san-pham/fern-tee')->assertOk()
            ->assertSee('Một nhành dương xỉ, in bằng ánh nắng.')   // câu trích
            ->assertSee('Lá dương xỉ thật được ép lên giấy cảm quang')  // khối nội dung
            ->assertSee('Bảng size')->assertSee('Ngang ngực (cm)')
            ->assertSee('Có thể bạn thích')->assertSee('Scrawl Tee')   // cùng danh mục áo thun
            ->assertDontSee('Reach Shirt')                              // khác danh mục
            ->assertDontSee('One of One Tee');                          // chưa mở bán
    }

    public function test_hidden_and_future_products_404(): void
    {
        $hidden = Product::factory()->create(['is_active' => false]);
        $future = Product::factory()->create(['is_active' => true, 'published_at' => now()->addDays(3)]);

        $this->get("/san-pham/{$hidden->slug}")->assertNotFound();
        $this->get("/san-pham/{$future->slug}")->assertNotFound();
        $this->get('/san-pham/khong-ton-tai')->assertNotFound();
    }

    public function test_bare_product_renders_without_blocks_or_images(): void
    {
        $p = Product::factory()->create(['is_active' => true, 'published_at' => now(), 'quote' => null]);

        $this->get("/san-pham/{$p->slug}")->assertOk()
            ->assertSee($p->name)->assertSee('Hết hàng')
            ->assertDontSee('Bảng size')->assertDontSee('Có thể bạn thích');
    }

    public function test_zalo_button_follows_settings(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/san-pham/fern-tee')->assertDontSee('zalo.me', false);

        app(ShopSettings::class)->set('zalo_url', 'https://zalo.me/0900000000');
        $this->get('/san-pham/fern-tee')->assertSee('https://zalo.me/0900000000', false);
    }

    public function test_picker_disables_sold_out_and_warns_low_stock(): void
    {
        $this->seed(CatalogSeeder::class);
        $p = Product::where('slug', 'scrawl-tee')->first();
        $color = $p->colors()->first();
        $p->variants()->where('product_color_id', $color->id)->where('size', 'L')->update(['stock' => 2]);

        Livewire::test(VariantPicker::class, ['product' => $p])
            ->set('colorId', $color->id)->set('size', 'L')
            ->assertSee('chỉ còn 2')
            ->assertSeeHtml('data-size="XL" disabled');
    }

    public function test_picker_dispatches_add_to_cart_for_the_selected_variant(): void
    {
        $this->seed(CatalogSeeder::class);
        $p = Product::where('slug', 'fern-tee')->first();
        $color = $p->colors()->skip(1)->first();
        $variant = $p->variants()->where('product_color_id', $color->id)->where('size', 'M')->first();

        Livewire::test(VariantPicker::class, ['product' => $p])
            ->call('add')->assertNotDispatched('add-to-cart')          // chưa chọn size
            ->set('colorId', $color->id)->assertDispatched('color-changed', colorId: $color->id)
            ->set('size', 'M')
            ->call('add')->assertDispatched('add-to-cart', variantId: $variant->id, buyNow: false)
            ->call('add', true)->assertDispatched('add-to-cart', variantId: $variant->id, buyNow: true);
    }

    public function test_picker_ignores_foreign_colour_unknown_size_and_sold_out_variant(): void
    {
        $this->seed(CatalogSeeder::class);
        $p = Product::where('slug', 'scrawl-tee')->first();
        $foreign = ProductColor::where('product_id', '!=', $p->id)->first();

        Livewire::test(VariantPicker::class, ['product' => $p])
            ->set('colorId', $foreign->id)->assertSet('colorId', $p->colors()->first()->id)
            ->set('size', 'XXL')->assertSet('size', null)
            ->set('size', 'XL')                                         // XL hết hàng
            ->call('add')->assertNotDispatched('add-to-cart');
    }

    public function test_picker_shows_variant_price_override(): void
    {
        $p = Product::factory()->create(['price' => 300000]);
        $c = ProductColor::factory()->for($p)->create();
        ProductVariant::factory()->for($p)->for($c, 'color')->create(['size' => 'M', 'stock' => 5, 'price' => 320000]);

        Livewire::test(VariantPicker::class, ['product' => $p])
            ->assertSee('300.000đ')
            ->set('size', 'M')->assertSee('320.000đ');
    }

    public function test_size_with_a_quote_does_not_break_the_click_handler(): void
    {
        $p = Product::factory()->create();
        $c = ProductColor::factory()->for($p)->create();
        ProductVariant::factory()->for($p)->for($c, 'color')->create(['size' => "5'6", 'stock' => 3]);

        Livewire::test(VariantPicker::class, ['product' => $p])
            ->assertDontSeeHtml("'5&#039;6'")
            ->set('size', "5'6")->assertSet('size', "5'6");
    }
}
