<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Pos;
use App\Models\Order;
use App\Models\User;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_requires_open_shift_then_shows_register(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff, 'admin')->get('/admin/ban-tai-quay')->assertOk()->assertSee('Mở ca');

        Livewire::actingAs($staff, 'admin')->test(Pos::class)
            ->set('openingCash', 500000)->call('openShift')
            ->assertHasNoErrors()->assertSee('Chốt ca');

        $this->assertSame(500000, app(ShiftService::class)->current()->opening_cash);
    }

    public function test_catalog_is_preloaded_for_scanning(): void
    {
        $v = $this->variant(['stock' => 4, 'barcode' => '893000000777', 'price' => 123000]);
        $staff = User::factory()->create();
        app(ShiftService::class)->open(0, $staff);

        $item = collect(Livewire::actingAs($staff, 'admin')->test(Pos::class)->instance()->catalog()['variants'])->firstWhere('id', $v->id);

        $this->assertSame(['893000000777', 123000, 4], [$item['barcode'], $item['price'], $item['stock']]);
    }

    public function test_lookup_trims_and_matches_sku_case_insensitively(): void
    {
        $v = $this->variant(['barcode' => '893000000123', 'sku' => 'SP0001-1-M']);
        $pos = Livewire::actingAs(User::factory()->create(), 'admin')->test(Pos::class)->instance();

        $this->assertSame($v->id, $pos->lookup(' 893000000123 ')['id']);
        $this->assertSame($v->id, $pos->lookup('sp0001-1-m')['id']);
        $this->assertNull($pos->lookup('nope'));
    }

    public function test_find_customer_by_phone(): void
    {
        \App\Models\Customer::create(['phone' => '0901234567', 'name' => 'Chị Hoa']);
        $pos = Livewire::actingAs(User::factory()->create(), 'admin')->test(Pos::class)->instance();

        $this->assertSame('Chị Hoa', $pos->findCustomer('090 123 4567')['name']);
        $this->assertNull($pos->findCustomer('0999999999'));
    }

    public function test_checkout_redirects_to_receipt(): void
    {
        $v = $this->variant(['stock' => 2, 'price' => 150000], ['name' => 'Áo sơ mi Linen']);
        $staff = User::factory()->create();
        app(ShiftService::class)->open(0, $staff);

        Livewire::actingAs($staff, 'admin')->test(Pos::class)
            ->call('checkout', [['id' => $v->id, 'qty' => 1]], 0, 'cash', 200000, null, null, 'tok-1')
            ->assertHasNoErrors()
            ->assertRedirect('/admin/don-hang/'.Order::firstOrFail()->code.'/hoa-don');

        $this->actingAs($staff, 'admin')->get('/admin/don-hang/'.Order::first()->code.'/hoa-don')
            ->assertOk()->assertSee('Áo sơ mi Linen')->assertSee('Tiền thối')->assertSee('50.000');
    }

    public function test_same_checkout_token_creates_one_order(): void
    {
        $v = $this->variant(['stock' => 5]);
        $staff = User::factory()->create();
        app(ShiftService::class)->open(0, $staff);
        $lines = [['id' => $v->id, 'qty' => 1]];

        Livewire::actingAs($staff, 'admin')->test(Pos::class)
            ->call('checkout', $lines, 0, 'qr', null, null, null, 'tok-1')
            ->call('checkout', $lines, 0, 'qr', null, null, null, 'tok-1');

        $this->assertSame([1, 4], [Order::count(), $v->fresh()->stock]);
    }

    public function test_checkout_without_open_shift_shows_error(): void
    {
        $v = $this->variant(['stock' => 5]);

        Livewire::actingAs(User::factory()->create(), 'admin')->test(Pos::class)
            ->call('checkout', [['id' => $v->id, 'qty' => 1]], 0, 'qr', null, null, null, 'tok-1')
            ->assertHasErrors('checkout');

        $this->assertSame(0, Order::count());
    }

    /** Ca bị chốt ở tab khác: vẫn ở màn bán (giỏ Alpine còn nguyên), thấy lỗi, mở ca ngay tại chỗ rồi bán tiếp. */
    public function test_shift_closed_elsewhere_keeps_register_and_reopens_inline(): void
    {
        $v = $this->variant(['stock' => 5]);
        $staff = User::factory()->create();
        $shifts = app(ShiftService::class);
        $shift = $shifts->open(0, $staff);
        $pos = Livewire::actingAs($staff, 'admin')->test(Pos::class);
        $shifts->close($shift, 0, $staff);
        $lines = [['id' => $v->id, 'qty' => 1]];

        $pos->call('checkout', $lines, 0, 'qr', null, null, null, 'tok-1')
            ->assertHasErrors('checkout')
            ->assertSee('Chưa mở ca')
            ->assertSee('x-data="pos(', false)
            ->set('openingCash', 0)->call('openShift')
            ->assertSee('x-data="pos(', false)
            ->call('checkout', $lines, 0, 'qr', null, null, null, 'tok-1')
            ->assertHasNoErrors();

        $this->assertSame(1, \App\Models\Order::count());
    }

    public function test_failed_checkout_lets_same_token_retry(): void
    {
        $v = $this->variant(['stock' => 5, 'price' => 100000]);
        $staff = User::factory()->create();
        app(ShiftService::class)->open(0, $staff);
        $lines = [['id' => $v->id, 'qty' => 1]];

        Livewire::actingAs($staff, 'admin')->test(Pos::class)
            ->call('checkout', $lines, 0, 'cash', 50000, null, null, 'tok-1')->assertHasErrors('checkout')
            ->call('checkout', $lines, 0, 'cash', 100000, null, null, 'tok-1')->assertHasNoErrors();

        $this->assertSame(1, Order::count());
    }

    public function test_receipt_only_for_pos_orders(): void
    {
        $order = $this->placeOrder();

        $this->actingAs(User::factory()->create(), 'admin')->get("/admin/don-hang/{$order->code}/hoa-don")->assertNotFound();
    }

    public function test_sidebar_links_pos(): void
    {
        $this->actingAs(User::factory()->create(), 'admin')->get('/admin')->assertSee('Bán tại quầy');
    }
}
