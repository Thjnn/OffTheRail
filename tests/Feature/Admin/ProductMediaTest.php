<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ProductMedia;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function media($product)
    {
        return Livewire::actingAs(User::factory()->owner()->create(), 'admin')->test(ProductMedia::class, ['product' => $product]);
    }

    public function test_upload_reorder_and_assign_color(): void
    {
        $v = $this->variant();
        $product = $v->product;

        $c = $this->media($product)
            ->set('uploads', [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')])
            ->call('upload')->assertHasNoErrors();

        [$a, $b] = $product->images()->get()->all();
        Storage::disk('public')->assertExists($a->path);
        $this->assertStringStartsWith("products/{$product->id}/", $a->path);

        $c->call('sortImages', $b->id, 0)->call('setColor', $a->id, $v->product_color_id);

        $this->assertSame([$b->id, $a->id], $product->images()->pluck('id')->all());
        $this->assertSame($v->product_color_id, $a->fresh()->product_color_id);
    }

    public function test_rejects_non_images_and_svg(): void
    {
        $product = $this->variant()->product;

        $this->media($product)->set('uploads', [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->call('upload')->assertHasErrors('uploads.0');
        $this->media($product)->set('uploads', [UploadedFile::fake()->create('x.svg', 1, 'image/svg+xml')])
            ->call('upload')->assertHasErrors('uploads.0');
        $this->media($product)->set('uploads', [UploadedFile::fake()->image('big.jpg')->size(5000)])
            ->call('upload')->assertHasErrors('uploads.0');

        $this->assertSame(0, ProductImage::count());
    }

    public function test_cannot_touch_other_products_media(): void
    {
        $mine = $this->variant()->product;
        $other = $this->variant()->product;
        $foreign = $other->images()->create(['path' => 'products/x.jpg', 'position' => 0]);

        $this->media($mine)->call('deleteImage', $foreign->id)->assertNotFound();
        $this->media($mine)->call('setColor', $foreign->id, null)->assertNotFound();

        $this->assertNotNull($foreign->fresh());
    }

    public function test_color_must_belong_to_product(): void
    {
        $mine = $this->variant()->product;
        $other = $this->variant();
        $image = $mine->images()->create(['path' => 'products/a.jpg', 'position' => 0]);

        $this->media($mine)->call('setColor', $image->id, $other->product_color_id);

        $this->assertNull($image->fresh()->product_color_id);
    }

    public function test_delete_image_removes_file(): void
    {
        $product = $this->variant()->product;
        $c = $this->media($product)->set('uploads', [UploadedFile::fake()->image('a.jpg')])->call('upload');
        $image = $product->images()->first();

        $c->call('deleteImage', $image->id);

        $this->assertNull($image->fresh());
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_block_needs_image_or_text(): void
    {
        $product = $this->variant()->product;

        $c = $this->media($product)->call('addBlock')
            ->set('blocks.0.body', '')->call('saveBlock', 0)->assertHasErrors('blocks.0.body');

        $c->set('blocks.0.body', 'Vải cotton 250gsm')->call('saveBlock', 0)->assertHasNoErrors();
        $this->assertSame('Vải cotton 250gsm', $product->blocks()->first()->body);

        $c->set('blockImages.0', UploadedFile::fake()->image('blk.jpg'))->set('blocks.0.body', '')->call('saveBlock', 0)->assertHasNoErrors();
        $this->assertNotNull($product->blocks()->first()->image_path);
    }

    public function test_product_page_shows_blocks_in_order(): void
    {
        $product = $this->variant()->product;
        $first = $product->blocks()->create(['body' => 'Đoạn một', 'position' => 0]);
        $second = $product->blocks()->create(['body' => 'Đoạn hai', 'position' => 1]);

        $this->media($product)->call('sortBlocks', $second->id, 0);

        $this->get('/san-pham/'.$product->slug)->assertOk()->assertSeeInOrder(['Đoạn hai', 'Đoạn một']);
    }

    public function test_staff_forbidden(): void
    {
        $product = $this->variant()->product;

        Livewire::actingAs(User::factory()->create(), 'admin')->test(ProductMedia::class, ['product' => $product])->assertForbidden();
    }
}
