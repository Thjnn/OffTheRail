<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\HomeSection;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\HomeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_featured_section_ranks_best_sellers(): void
    {
        HomeSection::create(['type' => 'featured', 'title' => 'Bán chạy', 'config' => ['limit' => 2], 'position' => 0]);
        $this->variant([], ['name' => 'Áo Nổi Bật Chưa Bán', 'is_featured' => true, 'published_at' => now()->subDay()]);
        $seller = $this->variant(['stock' => 10], ['name' => 'Áo Bán Chạy', 'published_at' => now()->subDays(20)]);
        $this->variant([], ['name' => 'Áo Thường Không Ai Mua']);

        $order = app(\App\Services\OrderService::class)->placeWebOrder([$seller->id => 3], $this->contact(), 'cod');
        // Đơn bị huỷ không tính vào bán chạy.
        $cancelled = $this->variant(['stock' => 10], ['name' => 'Áo Bị Huỷ']);
        $c = app(\App\Services\OrderService::class)->placeWebOrder([$cancelled->id => 5], $this->contact(['phone' => '0911111111']), 'cod');
        app(\App\Services\OrderService::class)->cancel($c);

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Áo Bán Chạy', 'Áo Nổi Bật Chưa Bán'])
            ->assertDontSee('Áo Bị Huỷ')
            ->assertDontSee('Áo Thường Không Ai Mua');
    }

    public function test_home_renders_sections_in_order(): void
    {
        $this->seed([CatalogSeeder::class, HomeSeeder::class]);

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Hàng mới', 'Mua theo loại', 'Bán chạy', 'Về chúng tôi'])
            ->assertSee('Fern Tee');
    }

    public function test_inactive_section_and_unknown_type_are_skipped(): void
    {
        HomeSection::create(['type' => 'story', 'title' => 'Ẩn đi', 'config' => [], 'position' => 1, 'is_active' => false]);
        HomeSection::create(['type' => 'khong_ro', 'title' => 'Lạ', 'config' => [], 'position' => 2, 'is_active' => true]);

        $this->get('/')->assertOk()->assertDontSee('Ẩn đi')->assertDontSee('Lạ');
    }

    public function test_only_active_banners_are_shown(): void
    {
        Banner::create(['image' => 'banners/a.webp', 'title' => 'Bộ sưu tập Thu', 'position' => 1, 'is_active' => true]);
        Banner::create(['image' => 'banners/b.webp', 'title' => 'Banner cũ', 'position' => 2, 'is_active' => false]);

        $this->get('/')->assertOk()->assertSee('Bộ sưu tập Thu')->assertDontSee('Banner cũ');
    }

    public function test_upcoming_products_do_not_appear_on_home(): void
    {
        $this->seed([CatalogSeeder::class, HomeSeeder::class]);

        $this->get('/')->assertOk()->assertDontSee('One of One Tee');
    }

    public function test_home_works_with_empty_database(): void
    {
        $this->get('/')->assertOk()->assertSee(shop('tagline'));
    }
}
