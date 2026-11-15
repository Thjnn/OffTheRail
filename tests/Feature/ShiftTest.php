<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\OrderActionException;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_sell_close_records_difference(): void
    {
        $staff = User::factory()->create();
        $shifts = app(ShiftService::class);
        $shift = $shifts->open(500000, $staff);

        $this->assertTrue($shift->isOpen());
        $this->assertSame($shift->id, $shifts->current()->id);

        $this->posOrder($shift, 'cash', 'paid', 300000);
        $this->posOrder($shift, 'qr', 'paid', 200000);
        $this->posOrder($shift, 'cash', 'refunded', 100000);

        $closed = $shifts->close($shift, 790000, $staff);

        $this->assertSame([800000, 790000, -10000], [$closed->expected_cash, $closed->counted_cash, $closed->difference()]);
        $this->assertFalse($closed->isOpen());
        $this->assertNull($shifts->current());
        $this->assertSame($staff->id, $closed->closed_by);
        $this->assertDatabaseHas('activity_logs', ['action' => 'shift.close', 'subject_id' => $shift->id]);
    }

    public function test_only_one_open_shift_for_the_shop(): void
    {
        app(ShiftService::class)->open(0, User::factory()->create(['name' => 'Lan']));

        try {
            app(ShiftService::class)->open(0, User::factory()->create());
            $this->fail('Mở ca thứ hai phải bị chặn');
        } catch (OrderActionException $e) {
            $this->assertStringContainsString('Lan', $e->getMessage());
        }

        $this->assertSame(1, Shift::count());
    }

    public function test_can_open_again_after_closing(): void
    {
        $staff = User::factory()->create();
        $shifts = app(ShiftService::class);
        $shifts->close($shifts->open(0, $staff), 0, $staff);

        $this->assertTrue($shifts->open(100000, $staff)->isOpen());
    }

    public function test_closing_twice_is_rejected(): void
    {
        $staff = User::factory()->create();
        $shift = app(ShiftService::class)->open(0, $staff);
        app(ShiftService::class)->close($shift, 0, $staff);

        $this->expectException(OrderActionException::class);
        app(ShiftService::class)->close($shift, 50000, $staff);
    }

    public function test_negative_opening_cash_rejected(): void
    {
        $this->expectException(OrderActionException::class);
        app(ShiftService::class)->open(-1, User::factory()->create());
    }

    private function posOrder(Shift $shift, string $method, string $paymentStatus, int $total): Order
    {
        return Order::create([
            'code' => Order::generateCode(),
            'channel' => 'pos',
            'customer_name' => 'Khách lẻ',
            'status' => $paymentStatus === 'refunded' ? OrderStatus::Cancelled : OrderStatus::Completed,
            'payment_method' => $method,
            'payment_status' => $paymentStatus,
            'subtotal' => $total,
            'total' => $total,
            'shift_id' => $shift->id,
        ]);
    }
}
