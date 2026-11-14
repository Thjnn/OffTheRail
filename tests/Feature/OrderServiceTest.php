<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\OutOfStockException;
use App\Models\Order;
use App\Models\StockMovement;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_places_order_with_server_side_totals(): void
    {
        $v = $this->variant(['stock' => 5, 'price' => null], ['price' => 200000]);

        $order = app(OrderService::class)->placeWebOrder([$v->id => 2], $this->contact(['phone' => '+84 901 234 567']), 'cod');

        $this->assertMatchesRegularExpression('/^DH[2-9A-HJ-NP-Z]{6}$/', $order->code);
        $this->assertSame([400000, 30000, 430000], [$order->subtotal, $order->shipping_fee, $order->total]);
        $this->assertSame('0901234567', $order->customer_phone);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(3, $v->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['variant_id' => $v->id, 'change' => -2, 'reason' => 'sale', 'order_id' => $order->id]);
        $this->assertDatabaseHas('order_status_logs', ['order_id' => $order->id, 'from' => null, 'to' => 'pending']);
        $this->assertSame($v->sku, $order->items->first()->sku);
        $this->assertSame('0901234567', $order->customer->phone);
    }

    public function test_free_shipping_at_threshold(): void
    {
        $v = $this->variant(['stock' => 5], ['price' => 250000]);

        $order = app(OrderService::class)->placeWebOrder([$v->id => 2], $this->contact(), 'cod');

        $this->assertSame(0, $order->shipping_fee);
    }

    public function test_last_unit_cannot_be_sold_twice(): void
    {
        $v = $this->variant(['stock' => 1]);
        app(OrderService::class)->placeWebOrder([$v->id => 1], $this->contact(), 'cod');

        try {
            app(OrderService::class)->placeWebOrder([$v->id => 1], $this->contact(['phone' => '0911111111']), 'cod');
            $this->fail('phải ném OutOfStockException');
        } catch (OutOfStockException $e) {
            $this->assertStringContainsString($v->product->name, $e->names[0]);
        }

        $this->assertSame(0, $v->fresh()->stock);
        $this->assertSame(1, Order::count());
    }

    public function test_one_short_line_rolls_back_whole_order(): void
    {
        $ok = $this->variant(['stock' => 5]);
        $short = $this->variant(['stock' => 1]);
        $hidden = $this->variant(['stock' => 5], ['is_active' => false]);

        try {
            app(OrderService::class)->placeWebOrder([$ok->id => 2, $short->id => 2, $hidden->id => 1, 999 => 1], $this->contact(), 'cod');
            $this->fail('phải ném OutOfStockException');
        } catch (OutOfStockException $e) {
            $this->assertCount(3, $e->names);
        }

        $this->assertSame(5, $ok->fresh()->stock);
        $this->assertSame(0, Order::count());
        $this->assertSame(0, StockMovement::count());
    }

    public function test_cancel_restores_stock_once(): void
    {
        $v = $this->variant(['stock' => 5]);
        $order = app(OrderService::class)->placeWebOrder([$v->id => 2], $this->contact(), 'cod');

        $this->assertTrue(app(OrderService::class)->cancel($order, 'khách đổi ý'));
        $this->assertFalse(app(OrderService::class)->cancel($order->fresh()));

        $this->assertSame(5, $v->fresh()->stock);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertDatabaseHas('stock_movements', ['variant_id' => $v->id, 'change' => 2, 'reason' => 'cancel']);
        $this->assertDatabaseHas('order_status_logs', ['order_id' => $order->id, 'from' => 'pending', 'to' => 'cancelled', 'note' => 'khách đổi ý']);
    }

    public function test_expired_transfer_orders_are_cancelled(): void
    {
        $v = $this->variant(['stock' => 10]);
        $svc = app(OrderService::class);
        $old = $svc->placeWebOrder([$v->id => 1], $this->contact(), 'bank_transfer');
        $paid = $svc->placeWebOrder([$v->id => 1], $this->contact(), 'bank_transfer');
        $cod = $svc->placeWebOrder([$v->id => 1], $this->contact(), 'cod');
        $fresh = $svc->placeWebOrder([$v->id => 1], $this->contact(), 'bank_transfer');

        Order::whereKey([$old->id, $paid->id, $cod->id])->update(['created_at' => now()->subHours(25)]);
        $paid->update(['payment_status' => 'paid']);
        $fresh->forceFill(['created_at' => now()->subHours(23)])->save();

        $this->artisan('orders:cancel-expired')->assertSuccessful();

        $this->assertSame(OrderStatus::Cancelled, $old->fresh()->status);
        foreach ([$paid, $cod, $fresh] as $o) {
            $this->assertSame(OrderStatus::Pending, $o->fresh()->status);
        }
        $this->assertSame(7, $v->fresh()->stock);
    }
}
