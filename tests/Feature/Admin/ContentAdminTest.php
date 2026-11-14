<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\AboutEditor;
use App\Livewire\Admin\BannerManager;
use App\Livewire\Admin\HomeSectionManager;
use App\Livewire\Admin\SizeChartEditor;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ContentAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function owner(): User
    {
        return User::factory()->owner()->create();
    }

    public function test_banner_crud_shows_on_home(): void
    {
        $c = Livewire::actingAs($this->owner(), 'admin')->test(BannerManager::class)
            ->set('image', UploadedFile::fake()->image('hero.jpg', 1600, 900))
            ->set('title', 'Bộ sưu tập Đông')->set('link', '/san-pham')->set('link_label', 'Xem ngay')
            ->call('create')->assertHasNoErrors();

        $banner = Banner::first();
        Storage::disk('public')->assertExists($banner->image);
        $this->get('/')->assertOk()->assertSee('Bộ sưu tập Đông');

        $c->set("rows.{$banner->id}.title", 'Bộ sưu tập Xuân')->call('update', $banner->id)->assertHasNoErrors();
        $this->assertSame('Bộ sưu tập Xuân', $banner->fresh()->title);

        $c->call('toggle', $banner->id);
        $this->get('/')->assertDontSee('Bộ sưu tập Xuân');

        $c->call('delete', $banner->id);
        $this->assertSame(0, Banner::count());
        Storage::disk('public')->assertMissing($banner->image);
    }

    public function test_banner_link_must_be_internal_or_https(): void
    {
        Livewire::actingAs($this->owner(), 'admin')->test(BannerManager::class)
            ->set('image', UploadedFile::fake()->image('hero.jpg'))
            ->set('link', 'javascript:alert(1)')->call('create')->assertHasErrors('link')
            ->set('link', '//evil.test')->call('create')->assertHasErrors('link')
            ->set('link', 'https://shopee.vn/shop')->call('create')->assertHasNoErrors();
    }

    public function test_section_edit_sort_and_hide(): void
    {
        $story = HomeSection::create(['type' => 'story', 'title' => 'Chuyện của xưởng', 'config' => ['body' => 'Ít mà chất'], 'position' => 0]);
        $new = HomeSection::create(['type' => 'new_arrivals', 'title' => 'Hàng mới', 'config' => ['limit' => 8], 'position' => 1]);
        $this->variant();

        $c = Livewire::actingAs($this->owner(), 'admin')->test(HomeSectionManager::class)
            ->set("rows.{$new->id}.limit", 4)->set("rows.{$new->id}.title", 'Vừa về')->call('save', $new->id)->assertHasNoErrors()
            ->set("rows.{$new->id}.limit", 99)->call('save', $new->id)->assertHasErrors("rows.{$new->id}.limit")
            ->call('sort', $new->id, 0);

        $this->assertSame(['limit' => 4], $new->fresh()->config);
        $this->assertSame(0, $new->fresh()->position);
        $this->get('/')->assertSeeInOrder(['Vừa về', 'Chuyện của xưởng']);

        $c->call('toggle', $story->id);
        $this->get('/')->assertDontSee('Chuyện của xưởng');

        $c->set('newType', 'featured')->call('add');
        $this->assertSame(1, HomeSection::where('type', 'featured')->count());
        $c->set('newType', 'hack')->call('add')->assertHasErrors('newType');
    }

    public function test_about_body_is_escaped_paragraphs(): void
    {
        Livewire::actingAs($this->owner(), 'admin')->test(AboutEditor::class)
            ->set('body', "<b>Đoạn một</b>\n\nĐoạn hai")->call('save')->assertHasNoErrors();

        $this->get('/gioi-thieu')->assertOk()
            ->assertSee('&lt;b&gt;Đoạn một&lt;/b&gt;', false)
            ->assertSee('<p class="about-p', false)
            ->assertSee('Đoạn hai')
            ->assertDontSee('Làm gần');
    }

    public function test_about_falls_back_to_default_copy(): void
    {
        $this->get('/gioi-thieu')->assertOk()->assertSee('Làm gần');
    }

    public function test_size_chart_editor(): void
    {
        $category = Category::factory()->create(['size_chart' => null]);

        $c = Livewire::actingAs($this->owner(), 'admin')->test(SizeChartEditor::class)
            ->set('categoryId', $category->id)
            ->set('text', "Size | Ngực\nM | 100")->call('save')->assertHasNoErrors();

        $this->assertSame(['columns' => ['Size', 'Ngực'], 'rows' => [['M', '100']]], $category->fresh()->size_chart);

        $c->set('text', '')->call('save');
        $this->assertNull($category->fresh()->size_chart);
    }

    public function test_staff_forbidden(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff, 'admin')->get('/admin/noi-dung')->assertForbidden();
        foreach ([BannerManager::class, HomeSectionManager::class, AboutEditor::class, SizeChartEditor::class] as $component) {
            Livewire::actingAs($staff, 'admin')->test($component)->assertForbidden();
        }
    }

    public function test_page_renders_for_owner(): void
    {
        $this->actingAs($this->owner(), 'admin')->get('/admin/noi-dung')->assertOk()->assertSee('Banner')->assertSee('Bảng size');
    }
}
