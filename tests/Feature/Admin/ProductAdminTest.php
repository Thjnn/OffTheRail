<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ProductForm;
use App\Livewire\Admin\ProductTable;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->owner()->create();
    }

    public function test_owner_creates_product_with_variant_matrix(): void
    {
        $owner = $this->owner();
        $category = Category::factory()->create();

        Livewire::actingAs($owner, 'admin')->test(ProductForm::class)
            ->set('name', 'Áo Thun Mây')->set('category_id', $category->id)->set('price', 300000)
            ->call('addColor')->set('colors.0.name', 'Đen')->set('colors.0.hex', '#111111')
            ->call('toggleCell', 0, 'M')->set('variants.0.stock', 5)
            ->call('toggleCell', 0, 'L')
            ->call('save')->assertHasNoErrors();

        $product = Product::firstWhere('slug', 'ao-thun-may');
        $this->assertNotNull($product);
        $variant = $product->variants()->where('size', 'M')->first();
        $this->assertSame(sprintf('SP%04d-1-M', $product->id), $variant->sku);
        $this->assertSame($variant->defaultBarcode(), $variant->barcode);
        $this->assertStringStartsWith('893', $variant->barcode);
        $this->assertSame(5, $variant->stock);
        $this->assertSame(0, $product->variants()->where('size', 'L')->first()->stock);
        $this->assertDatabaseHas('stock_movements', ['variant_id' => $variant->id, 'change' => 5, 'reason' => 'import', 'user_id' => $owner->id]);
        $this->assertSame(1, $product->variants()->where('size', 'L')->count());
    }

    public function test_edit_keeps_existing_stock_and_logs_price_change(): void
    {
        $v = $this->variant(['stock' => 7, 'price' => null], ['price' => 300000]);
        $owner = $this->owner();

        Livewire::actingAs($owner, 'admin')->test(ProductForm::class, ['product' => $v->product])
            ->assertSet('variants.0.id', $v->id)
            ->set('price', 320000)->set('variants.0.stock', 999) // tồn của biến thể cũ không sửa ở đây
            ->set('variants.0.price', 350000)
            ->call('save')->assertHasNoErrors();

        $this->assertSame(320000, $v->product->fresh()->price);
        $this->assertSame([7, 350000], [$v->fresh()->stock, $v->fresh()->price]);
        $this->assertSame(2, ActivityLog::where('action', 'price.update')->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'price.update', 'subject_id' => $v->product_id, 'user_id' => $owner->id]);
    }

    public function test_variant_with_orders_cannot_be_removed_and_product_cannot_be_deleted(): void
    {
        $order = $this->placeOrder();
        $variant = $order->items->first()->variant;
        $owner = $this->owner();

        Livewire::actingAs($owner, 'admin')->test(ProductForm::class, ['product' => $variant->product])
            ->call('toggleCell', 0, $variant->size)
            ->call('save')->assertHasErrors('variants');
        $this->assertNotNull($variant->fresh());

        Livewire::actingAs($owner, 'admin')->test(ProductForm::class, ['product' => $variant->product])
            ->call('removeColor', 0)->call('save')->assertHasErrors('variants');
        $this->assertNotNull($variant->fresh());

        Livewire::actingAs($owner, 'admin')->test(ProductForm::class, ['product' => $variant->product])
            ->call('delete')->assertHasErrors('product');
        $this->assertNotNull($variant->product->fresh());

        $this->actingAs($owner, 'admin')->get('/admin/don-hang/'.$order->code)->assertOk()->assertSee($order->items->first()->name);
    }

    public function test_product_without_orders_can_be_deleted(): void
    {
        $v = $this->variant();

        Livewire::actingAs($this->owner(), 'admin')->test(ProductForm::class, ['product' => $v->product])
            ->call('delete')->assertRedirect('/admin/san-pham');

        $this->assertNull($v->product->fresh());
    }

    public function test_validation_rejects_bad_input(): void
    {
        $taken = $this->variant(['sku' => 'TAKEN-1']);

        Livewire::actingAs($this->owner(), 'admin')->test(ProductForm::class)
            ->set('name', 'Áo')->set('category_id', $taken->product->category_id)->set('price', 300000)->set('compare_at_price', 300000)
            ->call('addColor')->set('colors.0.name', 'Đỏ')->set('colors.0.hex', 'red')
            ->call('toggleCell', 0, 'M')->set('variants.0.sku', 'TAKEN-1')
            ->set('variants', [['id' => null, 'color' => 0, 'size' => 'ZZ', 'sku' => 'TAKEN-1', 'barcode' => '', 'price' => null, 'stock' => -1]])
            ->call('save')
            ->assertHasErrors(['compare_at_price', 'colors.0.hex', 'variants.0.size', 'variants.0.sku', 'variants.0.stock']);

        $this->assertSame(1, Product::count());
    }

    public function test_duplicate_sku_inside_form_rejected(): void
    {
        $category = Category::factory()->create();

        Livewire::actingAs($this->owner(), 'admin')->test(ProductForm::class)
            ->set('name', 'Áo')->set('category_id', $category->id)->set('price', 1000)
            ->call('addColor')->set('colors.0.name', 'Đỏ')->set('colors.0.hex', '#aa0000')
            ->call('toggleCell', 0, 'M')->call('toggleCell', 0, 'L')
            ->set('variants.0.sku', 'SAME')->set('variants.1.sku', 'SAME')
            ->call('save')->assertHasErrors(['variants.0.sku']);
    }

    public function test_staff_can_list_but_not_edit(): void
    {
        $v = $this->variant([], ['name' => 'Áo Xem Thôi']);
        $staff = User::factory()->create();

        $this->actingAs($staff, 'admin')->get('/admin/san-pham')->assertOk()->assertSee('Áo Xem Thôi')->assertDontSee('Thêm sản phẩm');
        $this->get('/admin/san-pham/tao')->assertForbidden();
        $this->get('/admin/san-pham/'.$v->product_id.'/sua')->assertForbidden();

        Livewire::actingAs($staff, 'admin')->test(ProductForm::class, ['product' => $v->product])->assertForbidden();
    }

    public function test_table_search(): void
    {
        $this->variant([], ['name' => 'Áo Mây']);
        $this->variant([], ['name' => 'Quần Gió']);

        Livewire::actingAs($this->owner(), 'admin')->test(ProductTable::class)
            ->set('q', 'mây')->assertSee('Áo Mây')->assertDontSee('Quần Gió');
    }

    public function test_owner_pages_render(): void
    {
        $v = $this->variant();
        $this->actingAs($this->owner(), 'admin');

        $this->get('/admin/san-pham/tao')->assertOk();
        $this->get('/admin/san-pham/'.$v->product_id.'/sua')->assertOk()->assertSee($v->sku);
    }
}
