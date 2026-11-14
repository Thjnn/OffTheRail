<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\OrderActionException;
use App\Exceptions\OutOfStockException;
use App\Livewire\Admin\ShopeeOrderForm;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShopeeOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_shopee_order_takes_stock_and_is_paid(): void
    {
        $v = $this->variant(['stock' => 3, 'price' => null], ['price' => 200000]);
        $staff = User::factory()->create();

        $order = app(OrderService::class)->placeShopeeOrder(' 2411abc ', [$v->id => 2], 350000, $staff);

        $this->assertSame(['shopee', '2411ABC', 'completed', 'paid', null, $staff->id],
            [$order->channel, $order->external_code, $order->status->value, $order->payment_status, $order->shift_id, $order->staff_id]);
        $this->assertSame([400000, 50000, 350000, 0], [$order->subtotal, $order->discount, $order->total, $order->shipping_fee]);
        $this->assertNull($order->customer_id);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(1, $v->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['variant_id' => $v->id, 'change' => -2, 'reason' => 'sale', 'order_id' => $order->id, 'user_id' => $staff->id]);
        $this->assertDatabaseHas('order_status_logs', ['order_id' => $order->id, 'from' => null, 'to' => 'completed', 'user_id' => $staff->id]);
    }

    public function test_hidden_product_can_still_be_sold_on_shopee(): void
    {
        $v = $this->variant(['stock' => 1], ['is_active' => false]);

        $order = app(OrderService::class)->placeShopeeOrder('X1', [$v->id => 1], 100000, User::factory()->create());

        $this->assertSame(0, $v->fresh()->stock);
        $this->assertCount(1, $order->items);
    }

    public function test_duplicate_code_rejected_regardless_of_case_and_spaces(): void
    {
        $v = $this->variant(['stock' => 5]);
        $staff = User::factory()->create();
        app(OrderService::class)->placeShopeeOrder('2411ABC', [$v->id => 1], 100000, $staff);

        try {
            app(OrderService::class)->placeShopeeOrder(' 2411abc ', [$v->id => 1], 100000, $staff);
            $this->fail('phải ném OrderActionException');
        } catch (OrderActionException $e) {
            $this->assertSame('Đơn Shopee 2411ABC đã được nhập.', $e->getMessage());
        }

        $this->assertSame(4, $v->fresh()->stock);
        $this->assertSame(1, Order::count());
    }

    public function test_empty_code_rejected(): void
    {
        $this->expectException(OrderActionException::class);
        app(OrderService::class)->placeShopeeOrder('   ', [$this->variant()->id => 1], 1, User::factory()->create());
    }

    public function test_out_of_stock_rolls_back(): void
    {
        $a = $this->variant(['stock' => 5]);
        $b = $this->variant(['stock' => 1]);

        try {
            app(OrderService::class)->placeShopeeOrder('X2', [$a->id => 1, $b->id => 2], 100000, User::factory()->create());
            $this->fail('phải ném OutOfStockException');
        } catch (OutOfStockException) {
        }

        $this->assertSame([5, 1], [$a->fresh()->stock, $b->fresh()->stock]);
        $this->assertSame(0, Order::count());
    }

    public function test_owner_cancel_restocks_staff_cannot(): void
    {
        $v = $this->variant(['stock' => 3]);
        $staff = User::factory()->create();
        $order = app(OrderService::class)->placeShopeeOrder('X3', [$v->id => 1], 100000, $staff);

        try {
            app(OrderService::class)->transition($order, OrderStatus::Cancelled, $staff);
            $this->fail('phải ném OrderActionException');
        } catch (OrderActionException) {
        }
        $this->assertSame(2, $v->fresh()->stock);

        app(OrderService::class)->transition($order, OrderStatus::Cancelled, User::factory()->owner()->create());

        $this->assertSame(3, $v->fresh()->stock);
        $this->assertSame(['cancelled', 'refunded'], [$order->fresh()->status->value, $order->fresh()->payment_status]);
    }

    public function test_form_creates_order_and_redirects(): void
    {
        $v = $this->variant(['stock' => 3, 'sku' => 'TEE-1-M']);
        $staff = User::factory()->create();

        $component = Livewire::actingAs($staff, 'admin')->test(ShopeeOrderForm::class)
            ->set('search', 'TEE-1')->assertSee('TEE-1-M')
            ->set('externalCode', 'X1')->call('add', $v->id)->call('add', $v->id)
            ->set('amount', 100000)->call('save');

        $order = Order::first();
        $component->assertRedirect('/admin/don-hang/'.$order->code);
        $this->assertSame(1, $v->fresh()->stock);
    }

    public function test_form_validation_and_duplicate_error(): void
    {
        $v = $this->variant(['stock' => 3]);
        $staff = User::factory()->create();
        app(OrderService::class)->placeShopeeOrder('DUP', [$v->id => 1], 1000, $staff);

        Livewire::actingAs($staff, 'admin')->test(ShopeeOrderForm::class)
            ->call('save')->assertHasErrors(['externalCode', 'amount', 'lines'])
            ->set('externalCode', 'dup')->call('add', $v->id)->set('amount', 1000)->call('save')
            ->assertHasErrors(['externalCode']);

        $this->assertSame(1, Order::count());
    }

    public function test_page_is_open_to_staff(): void
    {
        $this->actingAs(User::factory()->create(), 'admin')->get('/admin/don-hang/shopee')->assertOk()->assertSee('Mã đơn Shopee');
    }
}
