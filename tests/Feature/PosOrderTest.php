<?php

namespace Tests\Feature;

use App\Exceptions\OrderActionException;
use App\Exceptions\OutOfStockException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ShiftService;
use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_sale_takes_stock_and_is_paid_in_open_shift(): void
    {
        $v = $this->variant(['stock' => 3, 'price' => null], ['price' => 200000]);
        $staff = User::factory()->create();
        $shift = app(ShiftService::class)->open(0, $staff);

        $order = $this->pos([$v->id => 2], 20000, 'cash', 400000, null, null, $staff);

        $this->assertSame(['pos', 'completed', 'cash', 'paid', $shift->id, $staff->id, 'Khách lẻ', null],
            [$order->channel, $order->status->value, $order->payment_method, $order->payment_status, $order->shift_id, $order->staff_id, $order->customer_name, $order->customer_id]);
        $this->assertSame([400000, 20000, 380000, 400000, 0], [$order->subtotal, $order->discount, $order->total, $order->cash_received, $order->shipping_fee]);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(1, $v->fresh()->stock);
        $this->assertCount(1, $order->items);
        $this->assertDatabaseHas('stock_movements', ['variant_id' => $v->id, 'change' => -2, 'reason' => 'sale', 'order_id' => $order->id]);
        $this->assertDatabaseHas('order_status_logs', ['order_id' => $order->id, 'from' => null, 'to' => 'completed', 'user_id' => $staff->id]);
    }

    public function test_phone_attaches_existing_customer(): void
    {
        $customer = Customer::create(['phone' => '0901234567', 'name' => 'Chị Hoa']);
        $v = $this->variant(['stock' => 3]);
        $staff = User::factory()->create();
        app(ShiftService::class)->open(0, $staff);

        $order = $this->pos([$v->id => 1], 0, 'qr', null, '+84 901 234 567', '', $staff);

        $this->assertSame([$customer->id, 'Chị Hoa', '0901234567'], [$order->customer_id, $order->customer_name, $order->customer_phone]);
        $this->assertSame(1, $customer->fresh()->orders_count);
    }

    public function test_new_phone_creates_customer(): void
    {
        $v = $this->variant(['stock' => 3]);
        $staff = User::factory()->create();
        app(ShiftService::class)->open(0, $staff);

        $order = $this->pos([$v->id => 1], 0, 'qr', null, '0912000111', 'Anh Minh', $staff);

        $this->assertSame('Anh Minh', Customer::where('phone', '0912000111')->value('name'));
        $this->assertSame('Anh Minh', $order->customer_name);
    }

    public function test_staff_discount_limited_owner_not(): void
    {
        app(ShopSettings::class)->set('staff_max_discount_percent', 10);
        $v = $this->variant(['stock' => 5, 'price' => 100000]);
        $staff = User::factory()->create();
        app(ShiftService::class)->open(0, $staff);

        try {
            $this->pos([$v->id => 1], 10001, 'qr', null, null, null, $staff);
            $this->fail('Nhân viên giảm quá 10% phải bị chặn');
        } catch (OrderActionException $e) {
            $this->assertStringContainsString('10%', $e->getMessage());
        }

        $this->assertSame(90000, $this->pos([$v->id => 1], 10000, 'qr', null, null, null, $staff)->total);
        $this->assertSame(50000, $this->pos([$v->id => 1], 50000, 'qr', null, null, null, User::factory()->owner()->create())->total);
    }

    public function test_discount_above_subtotal_or_negative_rejected(): void
    {
        $v = $this->variant(['stock' => 5, 'price' => 100000]);
        $owner = User::factory()->owner()->create();
        app(ShiftService::class)->open(0, $owner);

        foreach ([100001, -1] as $discount) {
            try {
                $this->pos([$v->id => 1], $discount, 'qr', null, null, null, $owner);
                $this->fail("Giảm {$discount} phải bị chặn");
            } catch (OrderActionException) {
            }
        }

        $this->assertSame(0, Order::count());
        $this->assertSame(5, $v->fresh()->stock);
    }

    public function test_cash_received_below_total_rejected(): void
    {
        $v = $this->variant(['stock' => 5, 'price' => 100000]);
        $staff = User::factory()->create();
        app(ShiftService::class)->open(0, $staff);

        $this->expectExceptionMessage('Tiền khách đưa chưa đủ.');
        $this->pos([$v->id => 1], 0, 'cash', 99000, null, null, $staff);
    }

    public function test_qr_sale_has_no_cash_received(): void
    {
        $v = $this->variant(['stock' => 5]);
        $staff = User::factory()->create();
        app(ShiftService::class)->open(0, $staff);

        $this->assertNull($this->pos([$v->id => 1], 0, 'qr', 999999, null, null, $staff)->cash_received);
    }

    public function test_closed_shift_rejected(): void
    {
        $v = $this->variant(['stock' => 5]);
        $staff = User::factory()->create();
        $shifts = app(ShiftService::class);
        $shifts->close($shifts->open(0, $staff), 0, $staff);

        try {
            $this->pos([$v->id => 1], 0, 'qr', null, null, null, $staff);
            $this->fail('Không có ca mở phải bị chặn');
        } catch (OrderActionException $e) {
            $this->assertStringContainsString('Chưa mở ca', $e->getMessage());
        }

        $this->assertSame([0, 5], [Order::count(), $v->fresh()->stock]);
    }

    public function test_out_of_stock_rolls_back_everything(): void
    {
        $a = $this->variant(['stock' => 5]);
        $b = $this->variant(['stock' => 0], ['name' => 'Áo hết hàng']);
        $staff = User::factory()->create();
        app(ShiftService::class)->open(0, $staff);

        try {
            $this->pos([$a->id => 1, $b->id => 1], 0, 'qr', null, null, null, $staff);
            $this->fail('Thiếu hàng phải báo lỗi');
        } catch (OutOfStockException $e) {
            $this->assertStringContainsString('Áo hết hàng', implode(',', $e->names));
        }

        $this->assertSame([0, 5], [Order::count(), $a->fresh()->stock]);
    }

    public function test_invalid_phone_rejected(): void
    {
        $v = $this->variant(['stock' => 5]);
        $staff = User::factory()->create();
        app(ShiftService::class)->open(0, $staff);

        $this->expectExceptionMessage('Số điện thoại không hợp lệ.');
        $this->pos([$v->id => 1], 0, 'qr', null, '123', null, $staff);
    }

    public function test_hidden_product_can_be_sold_at_counter(): void
    {
        $v = $this->variant(['stock' => 1], ['is_active' => false]);
        $staff = User::factory()->create();
        app(ShiftService::class)->open(0, $staff);

        $this->pos([$v->id => 1], 0, 'qr', null, null, null, $staff);

        $this->assertSame(0, $v->fresh()->stock);
    }

    public function test_vietqr_image_for_amount_before_order_exists(): void
    {
        $this->assertNull(\App\Support\VietQr::image(150000, 'POS 1'));

        app(ShopSettings::class)->set('bank_bin', '970436');
        app(ShopSettings::class)->set('bank_account_no', '0123456789');

        $url = \App\Support\VietQr::image(150000, 'POS 1');
        $this->assertStringContainsString('970436-0123456789', $url);
        $this->assertStringContainsString('amount=150000', $url);
        $this->assertStringContainsString('addInfo=POS+1', $url);
    }

    private function pos(array $items, int $discount, string $method, ?int $cash, ?string $phone, ?string $name, User $by): Order
    {
        return app(OrderService::class)->placePosOrder($items, $discount, $method, $cash, $phone, $name, $by);
    }
}
