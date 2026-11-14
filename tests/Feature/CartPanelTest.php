<?php

namespace Tests\Feature;

use App\Livewire\CartPanel;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_to_cart_event_adds_and_opens_drawer(): void
    {
        $v = $this->variant(['stock' => 5]);

        Livewire::test(CartPanel::class, ['drawer' => true])
            ->dispatch('add-to-cart', variantId: $v->id, buyNow: false)
            ->assertSet('open', true)->assertSee($v->product->name)
            ->assertDispatched('cart-updated', count: 1);
    }

    public function test_buy_now_redirects_to_checkout(): void
    {
        $v = $this->variant(['stock' => 5]);

        Livewire::test(CartPanel::class, ['drawer' => true])
            ->dispatch('add-to-cart', variantId: $v->id, buyNow: true)->assertRedirect('/thanh-toan');
    }

    public function test_sold_out_or_garbage_variant_shows_notice(): void
    {
        Livewire::test(CartPanel::class, ['drawer' => true])
            ->dispatch('add-to-cart', variantId: 'abc', buyNow: true)
            ->assertNoRedirect()->assertSee('vừa hết hàng');
    }

    public function test_qty_and_remove(): void
    {
        $v = $this->variant(['stock' => 5], ['price' => 100000]);
        app(Cart::class)->add($v->id);

        Livewire::test(CartPanel::class)
            ->call('setQty', $v->id, 3)->assertSee('300.000đ')
            ->call('remove', $v->id)->assertSee('Giỏ hàng đang trống');
    }

    public function test_cart_page_and_header_badge(): void
    {
        $v = $this->variant(['stock' => 5]);
        app(Cart::class)->add($v->id, 2);

        $this->get('/gio-hang')->assertOk()->assertSee($v->product->name)
            ->assertSee('count: 2', false)->assertDontSee('data-cart-drawer', false);
        $this->get('/')->assertSee('data-cart-drawer', false);
    }

    public function test_short_stock_line_blocks_checkout_button(): void
    {
        $v = $this->variant(['stock' => 5]);
        app(Cart::class)->add($v->id, 3);
        $v->update(['stock' => 1]);

        Livewire::test(CartPanel::class)->assertSee('Chỉ còn 1')->assertSeeHtml('data-checkout disabled');
    }
}
