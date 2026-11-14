<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\OrderActionException;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): OrderService
    {
        return app(OrderService::class);
    }

    private function stockOf(Order $order): int
    {
        return $order->items->first()->variant->fresh()->stock;
    }

    public function test_full_cod_flow_marks_paid_on_completion(): void
    {
        $order = $this->placeOrder();
        $staff = User::factory()->create();

        $this->svc()->transition($order, OrderStatus::Confirmed, $staff);
        $this->svc()->transition($order, OrderStatus::Shipping, $staff, null, ' GHN123 ');
        $this->svc()->transition($order, OrderStatus::Completed, $staff);

        $order->refresh();
        $this->assertSame(['completed', 'paid', 'GHN123'], [$order->status->value, $order->payment_status, $order->tracking_code]);
        $this->assertNotNull($order->paid_at);
        $this->assertSame($staff->id, $order->statusLogs->last()->user_id);
        $this->assertSame([1, $order->total], [$order->customer->orders_count, $order->customer->total_spent]);
    }

    public function test_new_order_counts_but_unpaid_does_not_add_spending(): void
    {
        $order = $this->placeOrder();

        $this->assertSame([1, 0], [$order->customer->fresh()->orders_count, $order->customer->fresh()->total_spent]);
    }

    public function test_shipping_requires_tracking_code(): void
    {
        $order = $this->placeOrder();
        $staff = User::factory()->create();
        $this->svc()->transition($order, OrderStatus::Confirmed, $staff);

        try {
            $this->svc()->transition($order, OrderStatus::Shipping, $staff, null, '  ');
            $this->fail('phải ném OrderActionException');
        } catch (OrderActionException $e) {
            $this->assertSame('Cần nhập mã vận đơn.', $e->getMessage());
        }

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
    }

    public function test_illegal_transition_is_rejected(): void
    {
        $order = $this->placeOrder();

        $this->expectException(OrderActionException::class);
        $this->expectExceptionMessage('Đơn đang ở trạng thái «Chờ xác nhận», không chuyển sang «Hoàn thành» được.');

        $this->svc()->transition($order, OrderStatus::Completed, User::factory()->create());
    }

    public function test_double_cancel_restocks_once(): void
    {
        $order = $this->placeOrder();
        $stale = Order::find($order->id); // một người khác mở cùng đơn
        $staff = User::factory()->create();
        $before = $this->stockOf($order);

        $this->svc()->transition($order, OrderStatus::Cancelled, $staff, 'Khách huỷ');

        try {
            $this->svc()->transition($stale, OrderStatus::Cancelled, $staff);
            $this->fail('phải ném OrderActionException');
        } catch (OrderActionException) {
        }

        $this->assertSame($before + 1, $this->stockOf($order));
        $this->assertSame(1, StockMovement::where('order_id', $order->id)->where('reason', 'cancel')->count());
        $this->assertSame('Khách huỷ', $order->fresh()->statusLogs->last()->note);
    }

    public function test_staff_cannot_cancel_paid_order_but_owner_refunds(): void
    {
        $order = $this->placeOrder();
        $staff = User::factory()->create();
        $owner = User::factory()->owner()->create();
        $this->svc()->markPaid($order, $staff);
        $this->assertSame($order->total, $order->customer->fresh()->total_spent);
        $before = $this->stockOf($order);

        try {
            $this->svc()->transition($order, OrderStatus::Cancelled, $staff);
            $this->fail('phải ném OrderActionException');
        } catch (OrderActionException $e) {
            $this->assertSame('Chỉ chủ shop được huỷ/hoàn đơn đã thanh toán.', $e->getMessage());
        }
        $this->assertSame($before, $this->stockOf($order));

        $this->actingAs($owner, 'admin');
        $this->svc()->transition($order, OrderStatus::Cancelled, $owner);

        $order->refresh();
        $this->assertSame(['cancelled', 'refunded'], [$order->status->value, $order->payment_status]);
        $this->assertSame($before + 1, $this->stockOf($order));
        $this->assertDatabaseHas('activity_logs', ['action' => 'order.cancel', 'subject_id' => $order->id, 'user_id' => $owner->id]);
        $this->assertSame([0, 0], [$order->customer->fresh()->orders_count, $order->customer->fresh()->total_spent]);
    }

    public function test_return_after_completion_is_owner_only_and_restocks(): void
    {
        $order = $this->placeOrder();
        $staff = User::factory()->create();
        $owner = User::factory()->owner()->create();
        $this->svc()->transition($order, OrderStatus::Confirmed, $staff);
        $this->svc()->transition($order, OrderStatus::Shipping, $staff, null, 'GHN1');
        $this->svc()->transition($order, OrderStatus::Completed, $staff);
        $before = $this->stockOf($order);

        try {
            $this->svc()->transition($order, OrderStatus::Returned, $staff);
            $this->fail('phải ném OrderActionException');
        } catch (OrderActionException) {
        }

        $this->svc()->transition($order, OrderStatus::Returned, $owner, 'Khách trả lại');

        $this->assertSame(['returned', 'refunded'], [$order->fresh()->status->value, $order->fresh()->payment_status]);
        $this->assertSame($before + 1, $this->stockOf($order));
        $this->assertDatabaseHas('stock_movements', ['order_id' => $order->id, 'reason' => 'return', 'user_id' => $owner->id]);
        $this->assertSame(1, ActivityLog::where('action', 'order.return')->count());
    }

    public function test_staff_can_mark_unpaid_bounce_as_returned(): void
    {
        // Khách bom hàng COD: đơn đang giao, chưa thu tiền → nhân viên được ghi nhận hoàn.
        $order = $this->placeOrder();
        $staff = User::factory()->create();
        $this->svc()->transition($order, OrderStatus::Confirmed, $staff);
        $this->svc()->transition($order, OrderStatus::Shipping, $staff, null, 'GHN1');

        $this->svc()->transition($order, OrderStatus::Returned, $staff);

        $this->assertSame(['returned', 'unpaid'], [$order->fresh()->status->value, $order->fresh()->payment_status]);
    }

    public function test_mark_paid_only_once(): void
    {
        $order = $this->placeOrder();
        $staff = User::factory()->create();

        $this->svc()->markPaid($order, $staff);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('Đã nhận tiền', $order->fresh()->statusLogs->last()->note);

        $this->expectException(OrderActionException::class);
        $this->expectExceptionMessage('Đơn này không ở trạng thái chờ thanh toán.');
        $this->svc()->markPaid($order, $staff);
    }

    public function test_next_statuses(): void
    {
        $order = $this->placeOrder();

        $this->assertSame([OrderStatus::Confirmed, OrderStatus::Cancelled], $this->svc()->nextStatuses($order));

        $order->status = OrderStatus::Completed;
        $order->channel = 'shopee';
        $this->assertSame([OrderStatus::Cancelled], $this->svc()->nextStatuses($order));

        $order->status = OrderStatus::Cancelled;
        $this->assertSame([], $this->svc()->nextStatuses($order));
    }
}
