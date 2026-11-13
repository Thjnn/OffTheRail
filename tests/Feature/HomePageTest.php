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
