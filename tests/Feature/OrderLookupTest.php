<?php

namespace Tests\Feature;

use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLookupTest extends TestCase
{
    use RefreshDatabase;

    private const NOT_FOUND = 'Không tìm thấy đơn với mã và số điện thoại này.';

    public function test_finds_order_by_code_and_phone_in_any_format(): void
    {
        $order = $this->placeOrder(['phone' => '0901234567']);

        $this->post('/tra-cuu-don', ['code' => ' '.strtolower($order->code).' ', 'phone' => '+84 901 234 567'])
            ->assertOk()->assertSee($order->code)->assertSee('Chờ xác nhận')->assertSee($order->items->first()->name);
    }

    public function test_wrong_phone_and_wrong_code_give_same_error(): void
    {
        $order = $this->placeOrder(['phone' => '0901234567']);

        $this->post('/tra-cuu-don', ['code' => $order->code, 'phone' => '0911111111'])->assertSessionHasErrors(['code' => self::NOT_FOUND]);
        $this->post('/tra-cuu-don', ['code' => 'DHXXXXXX', 'phone' => '0901234567'])->assertSessionHasErrors(['code' => self::NOT_FOUND]);
        $this->post('/tra-cuu-don', ['code' => $order->code, 'phone' => 'abc'])->assertSessionHasErrors(['code' => self::NOT_FOUND]);
    }

    public function test_lookup_is_throttled(): void
    {
        foreach (range(1, 10) as $_) {
            $this->post('/tra-cuu-don', ['code' => 'X', 'phone' => '0901234567']);
        }

        $this->post('/tra-cuu-don', ['code' => 'X', 'phone' => '0901234567'])->assertStatus(429);
    }

    public function test_shows_cancel_note_in_timeline(): void
    {
        $order = $this->placeOrder(['phone' => '0901234567']);
        app(OrderService::class)->cancel($order, 'Quá 24 giờ chưa nhận được chuyển khoản');

        $this->post('/tra-cuu-don', ['code' => $order->code, 'phone' => '0901234567'])
            ->assertSee('Đã huỷ')->assertSee('Quá 24 giờ chưa nhận được chuyển khoản');
    }

    public function test_form_renders(): void
    {
        $this->get('/tra-cuu-don')->assertOk()->assertSee('Tra cứu đơn');
    }
}
