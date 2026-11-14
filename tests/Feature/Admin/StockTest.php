<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\StockTable;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_and_count(): void
    {
        $v = $this->variant(['stock' => 5]);
        $owner = User::factory()->owner()->create();
        $this->actingAs($owner, 'admin');

        $this->assertSame(8, Inventory::adjust($v->id, 'import', 3, 'Nhập lô mới', $owner));
        $this->assertSame(2, Inventory::adjust($v->id, 'count', 2, 'Kiểm kho cuối tháng', $owner));
        $this->assertSame(2, Inventory::adjust($v->id, 'count', 2, 'Đếm lại', $owner)); // không đổi → không ghi

        $this->assertSame(2, $v->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['variant_id' => $v->id, 'change' => 3, 'reason' => 'import', 'user_id' => $owner->id, 'note' => 'Nhập lô mới']);
        $this->assertDatabaseHas('stock_movements', ['variant_id' => $v->id, 'change' => -6, 'reason' => 'adjust', 'user_id' => $owner->id]);
        $this->assertSame(2, ActivityLog::where('action', 'stock.adjust')->count());
        $this->assertSame(['stock' => 8], ActivityLog::where('action', 'stock.adjust')->latest('id')->first()->old);
    }

    public function test_rejects_bad_adjustments(): void
    {
        $v = $this->variant(['stock' => 5]);
        $owner = User::factory()->owner()->create();

        foreach ([['import', 0, 'x'], ['count', -1, 'x'], ['import', 2, '  '], ['hack', 2, 'x']] as [$mode, $qty, $note]) {
            try {
                Inventory::adjust($v->id, $mode, $qty, $note, $owner);
                $this->fail("phải từ chối {$mode} {$qty}");
            } catch (InvalidArgumentException) {
            }
        }

        $this->assertSame(5, $v->fresh()->stock);
    }

    public function test_owner_adjusts_through_table(): void
    {
        $v = $this->variant(['stock' => 5]);

        Livewire::actingAs(User::factory()->owner()->create(), 'admin')->test(StockTable::class)
            ->call('openAdjust', $v->id)
            ->set('mode', 'count')->set('qty', 9)->set('note', '')->call('adjust')->assertHasErrors('note')
            ->set('note', 'Kiểm kho')->call('adjust')->assertHasNoErrors();

        $this->assertSame(9, $v->fresh()->stock);
    }

    public function test_staff_views_but_cannot_adjust(): void
    {
        $v = $this->variant(['stock' => 5]);
        $staff = User::factory()->create();

        $this->actingAs($staff, 'admin')->get('/admin/kho')->assertOk()->assertSee($v->sku)->assertDontSee('Điều chỉnh');

        Livewire::actingAs($staff, 'admin')->test(StockTable::class)
            ->set('adjustingId', $v->id)->set('mode', 'import')->set('qty', 3)->set('note', 'Hack')
            ->call('adjust')->assertForbidden();

        $this->assertSame(5, $v->fresh()->stock);
    }

    public function test_history_lists_movements(): void
    {
        $order = $this->placeOrder();
        $variant = $order->items->first()->variant;

        Livewire::actingAs(User::factory()->create(), 'admin')->test(StockTable::class)
            ->call('showHistory', $variant->id)->assertSee('Bán')->assertSee($order->code);
    }

    public function test_low_filter_and_export(): void
    {
        $low = $this->variant(['stock' => 2, 'sku' => 'LOW-1']);
        $this->variant(['stock' => 9, 'sku' => 'MANY-1']);

        Livewire::actingAs(User::factory()->create(), 'admin')->test(StockTable::class)
            ->set('low', true)->assertSee('LOW-1')->assertDontSee('MANY-1');

        $response = $this->actingAs(User::factory()->owner()->create(), 'admin')->get('/admin/kho/xuat-excel');
        $response->assertOk()->assertDownload('ton-kho-'.now()->format('Y-m-d').'.xlsx');
        $rows = OrderExportTest::readXlsx($response->streamedContent());
        $this->assertSame('SKU', $rows[0][0]);
        $this->assertCount(3, $rows);
        $this->assertContains(['LOW-1', $low->barcode, $low->product->name, $low->color->name, $low->size, 2, $low->product->price], $rows);

        $this->actingAs(User::factory()->create(), 'admin')->get('/admin/kho/xuat-excel')->assertForbidden();
    }
}
