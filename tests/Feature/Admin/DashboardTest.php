<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use App\Models\User;
use App\Support\SalesReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function order(string $channel, int $total, string $paymentStatus, Carbon $at, ?int $staffId = null, OrderStatus $status = OrderStatus::Completed): Order
    {
        $order = Order::create([
            'code' => Order::generateCode(), 'channel' => $channel, 'customer_name' => 'Khách',
            'status' => $status, 'payment_method' => 'cod', 'payment_status' => $paymentStatus,
            'paid_at' => $paymentStatus === 'unpaid' ? null : $at, 'subtotal' => $total, 'total' => $total, 'staff_id' => $staffId,
        ]);
        $order->forceFill(['created_at' => $at])->saveQuietly();

        return $order;
    }

    public function test_revenue_counts_paid_orders_by_channel_and_day(): void
    {
        $this->order('web', 100000, 'paid', now()->setTime(10, 0));
        $this->order('shopee', 50000, 'paid', now()->subDay()->setTime(23, 30));
        $this->order('web', 70000, 'unpaid', now()->setTime(9, 0), null, OrderStatus::Pending);
        $this->order('web', 30000, 'refunded', now()->setTime(8, 0), null, OrderStatus::Cancelled);
        $this->order('web', 999000, 'paid', now()->subDays(10));

        $r = new SalesReport(now()->subDays(6)->startOfDay(), now()->endOfDay());

        $this->assertSame(150000, $r->revenue());
        $this->assertSame(3, $r->orderCount()); // trừ đơn đã huỷ
        $d = $r->daily();
        $this->assertCount(7, $d['labels']);
        $this->assertSame(now()->format('d/m'), end($d['labels']));
        $this->assertSame(100000, end($d['series']['web']));
        $this->assertSame(50000, $d['series']['shopee'][5]);
        $this->assertSame(0, array_sum($d['series']['pos']));
    }

    public function test_previous_period(): void
    {
        $this->order('web', 40000, 'paid', now()->subDays(8));

        $prev = (new SalesReport(now()->subDays(6)->startOfDay(), now()->endOfDay()))->previous();

        $this->assertSame(40000, $prev->revenue());
    }

    public function test_staff_sees_only_own_sales(): void
    {
        $me = User::factory()->create();
        $this->order('shopee', 50000, 'paid', now(), $me->id);
        $this->order('shopee', 80000, 'paid', now(), User::factory()->create()->id);

        $this->assertSame(50000, (new SalesReport(now()->startOfDay(), now()->endOfDay(), $me->id))->revenue());
    }

    public function test_change_percent(): void
    {
        $this->assertSame(50, SalesReport::change(150, 100));
        $this->assertSame(-25, SalesReport::change(75, 100));
        $this->assertNull(SalesReport::change(10, 0));
    }

    public function test_greeting(): void
    {
        $this->assertSame('Chào buổi sáng, Hà', Dashboard::greeting(8, 'Hà'));
        $this->assertSame('Chào buổi chiều, Hà', Dashboard::greeting(14, 'Hà'));
        $this->assertSame('Chào buổi tối, Hà', Dashboard::greeting(20, 'Hà'));
    }

    public function test_page_renders_cards_and_low_stock(): void
    {
        $this->variant(['stock' => 1], ['name' => 'Áo Sắp Hết']);
        $this->placeOrder();

        $this->actingAs(User::factory()->owner()->create(), 'admin');
        $this->get('/admin')->assertOk()->assertSee('Doanh thu')->assertSee('Áo Sắp Hết')->assertSee('Chờ xử lý');
        $this->get('/admin?range=999')->assertOk();
    }

    public function test_staff_dashboard_shows_own_sales_label(): void
    {
        $this->actingAs(User::factory()->create(), 'admin')->get('/admin')->assertOk()->assertSee('Doanh số của bạn');
    }
}
