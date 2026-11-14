<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Livewire\Admin\OrderTable;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_filters_by_status_channel_and_query(): void
    {
        $staff = User::factory()->create();
        $pending = $this->placeOrder();
        $confirmed = $this->placeOrder(['phone' => '0911111111']);
        app(OrderService::class)->transition($confirmed, OrderStatus::Confirmed, $staff);

        Livewire::actingAs($staff, 'admin')->test(OrderTable::class)
            ->assertSee($pending->code)->assertSee($confirmed->code)
            ->set('status', 'pending')->assertSee($pending->code)->assertDontSee($confirmed->code)
            ->set('status', '')->set('q', '+84 911 111 111')->assertSee($confirmed->code)->assertDontSee($pending->code)
            ->set('q', '')->set('channel', 'shopee')->assertDontSee($pending->code)
            ->set('channel', '')->set('status', 'hack')->assertOk()->assertSee($pending->code)->assertSee($confirmed->code);
    }

    public function test_staff_moves_order_forward(): void
    {
        $order = $this->placeOrder();

        $this->actingAs(User::factory()->create(), 'admin')
            ->post("/admin/don-hang/{$order->code}/trang-thai", ['to' => 'confirmed'])
            ->assertRedirect()->assertSessionHas('status');

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
    }

    public function test_tracking_code_is_saved_when_shipping(): void
    {
        $order = $this->placeOrder();
        $this->actingAs(User::factory()->create(), 'admin');
        $this->post("/admin/don-hang/{$order->code}/trang-thai", ['to' => 'confirmed']);

        $this->post("/admin/don-hang/{$order->code}/trang-thai", ['to' => 'shipping'])->assertSessionHasErrors('order');
        $this->post("/admin/don-hang/{$order->code}/trang-thai", ['to' => 'shipping', 'tracking_code' => 'GHN99']);

        $this->assertSame('GHN99', $order->fresh()->tracking_code);
    }

    public function test_rejected_action_shows_error_not_500(): void
    {
        $order = $this->placeOrder();
        $staff = User::factory()->create();
        app(OrderService::class)->markPaid($order, $staff);

        $this->actingAs($staff, 'admin')
            ->post("/admin/don-hang/{$order->code}/trang-thai", ['to' => 'cancelled'])
            ->assertSessionHasErrors(['order' => 'Chỉ chủ shop được huỷ/hoàn đơn đã thanh toán.']);
        $this->post("/admin/don-hang/{$order->code}/trang-thai", ['to' => 'hack'])->assertSessionHasErrors('to');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_mark_paid_route(): void
    {
        $order = $this->placeOrder();

        $this->actingAs(User::factory()->create(), 'admin')->post("/admin/don-hang/{$order->code}/da-thanh-toan")->assertRedirect();
        $this->assertSame('paid', $order->fresh()->payment_status);

        $this->post("/admin/don-hang/{$order->code}/da-thanh-toan")->assertSessionHasErrors('order');
    }

    public function test_detail_and_print_pages(): void
    {
        $order = $this->placeOrder();
        $this->actingAs(User::factory()->create(), 'admin');

        $this->get("/admin/don-hang/{$order->code}")->assertOk()
            ->assertSee('Nguyễn An')->assertSee('0901234567')->assertSee('Xác nhận đơn')->assertSee('Đã nhận tiền');
        $this->get("/admin/don-hang/{$order->code}/in")->assertOk()->assertSee($order->code)->assertSee('Thu hộ');
    }

    public function test_guest_cannot_see_orders(): void
    {
        $this->get('/admin/don-hang')->assertRedirect('/admin/dang-nhap');
    }
}
