<?php

namespace Tests\Feature;

use App\Livewire\Checkout;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\Cart;
use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->locations();
    }

    private function fill(Testable $c): Testable
    {
        return $c->set('name', 'Nguyễn An')->set('phone', '0901234567')
            ->set('provinceCode', '01')->set('wardCode', '00004')->set('street', '1 Tràng Tiền');
    }

    public function test_places_cod_order_and_clears_cart(): void
    {
        $v = $this->variant(['stock' => 5], ['price' => 200000]);
        app(Cart::class)->add($v->id, 2);

        $c = $this->fill(Livewire::test(Checkout::class))->set('phone', '+84 901 234 567')->call('place');

        $order = Order::first();
        $c->assertRedirect('/don-hang/'.$order->code.'/thanh-cong');
        $this->assertSame([430000, 'cod', '0901234567'], [$order->total, $order->payment_method, $order->customer_phone]);
        $this->assertSame('01', $order->address['province_code']);
        $this->assertTrue(app(Cart::class)->isEmpty());
    }

    public function test_empty_cart_redirects_to_cart(): void
    {
        Livewire::test(Checkout::class)->assertRedirect('/gio-hang');
    }

    public function test_double_submit_creates_one_order(): void
    {
        $v = $this->variant(['stock' => 5]);
        app(Cart::class)->add($v->id, 2);

        $c = $this->fill(Livewire::test(Checkout::class));
        $c->call('place');
        $c->call('place');

        $this->assertSame(1, Order::count());
        $this->assertSame(3, $v->fresh()->stock);
    }

    public function test_rejects_garbage_input(): void
    {
        $v = $this->variant(['stock' => 5]);
        app(Cart::class)->add($v->id);

        $this->fill(Livewire::test(Checkout::class))
            ->set('phone', '12345')->set('provinceCode', '01')->set('wardCode', '26734') // phường của tỉnh khác
            ->set('paymentMethod', 'hack')->call('place')
            ->assertHasErrors(['phone', 'wardCode', 'paymentMethod']);
        $this->fill(Livewire::test(Checkout::class))->set('provinceCode', 'ZZ')->call('place')->assertHasErrors(['provinceCode']);
        $this->fill(Livewire::test(Checkout::class))->set('paymentMethod', 'bank_transfer')->call('place')
            ->assertHasErrors(['paymentMethod']); // shop chưa cấu hình ngân hàng

        $this->assertSame(0, Order::count());
    }

    public function test_out_of_stock_at_submit_shows_item_name(): void
    {
        $v = $this->variant(['stock' => 2]);
        app(Cart::class)->add($v->id, 2);
        $c = $this->fill(Livewire::test(Checkout::class));

        $v->update(['stock' => 1]);

        $c->call('place')->assertHasErrors('cart')->assertSee($v->product->name)->assertNoRedirect();
        $this->assertSame(0, Order::count());
    }

    public function test_honeypot_creates_no_order(): void
    {
        $v = $this->variant(['stock' => 5]);
        app(Cart::class)->add($v->id);

        $this->fill(Livewire::test(Checkout::class))->set('website', 'http://x')->call('place');

        $this->assertSame(0, Order::count());
    }

    public function test_shipping_updates_with_province(): void
    {
        app(ShopSettings::class)->set('shipping_fees', ['79' => 20000]);
        $v = $this->variant(['stock' => 5], ['price' => 100000]);
        app(Cart::class)->add($v->id);

        Livewire::test(Checkout::class)->set('provinceCode', '79')->assertSee('20.000đ')->assertSee('120.000đ');
    }

    public function test_success_page_shows_qr_without_personal_data(): void
    {
        foreach (['bank_bin' => '970436', 'bank_account_no' => '0123456789', 'bank_account_name' => 'TIEM AO HA'] as $k => $val) {
            app(ShopSettings::class)->set($k, $val);
        }
        $v = $this->variant(['stock' => 5]);
        $order = app(OrderService::class)->placeWebOrder(
            [$v->id => 1], $this->contact(['name' => 'Nguyễn Văn Kín', 'street' => '12 Ngõ Riêng']), 'bank_transfer'
        );

        $this->get("/don-hang/{$order->code}/thanh-cong")->assertOk()
            ->assertSee($order->code)
            ->assertSee("img.vietqr.io/image/970436-0123456789-compact2.png?amount={$order->total}&amp;addInfo={$order->code}", false)
            ->assertDontSee('Nguyễn Văn Kín')->assertDontSee('12 Ngõ Riêng')->assertDontSee($order->customer_phone);
        $this->get('/don-hang/DHKHONGCO/thanh-cong')->assertNotFound();
    }
}
