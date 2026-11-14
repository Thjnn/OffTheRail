<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_accumulates_and_clamps_to_stock(): void
    {
        $v = $this->variant(['stock' => 3]);
        $cart = app(Cart::class);

        $this->assertTrue($cart->add($v->id, 2));
        $this->assertTrue($cart->add($v->id, 5));
        $this->assertSame([$v->id => 3], $cart->items());
    }

    public function test_add_rejects_missing_hidden_and_sold_out(): void
    {
        $cart = app(Cart::class);

        $this->assertFalse($cart->add(999));
        $this->assertFalse($cart->add($this->variant(['stock' => 0])->id));
        $this->assertFalse($cart->add($this->variant(['stock' => 5], ['is_active' => false])->id));
        $this->assertTrue($cart->isEmpty());
    }

    public function test_totals_use_current_db_price_and_variant_override(): void
    {
        $a = $this->variant(['stock' => 5, 'price' => null], ['price' => 200000]);
        $b = $this->variant(['stock' => 5, 'price' => 150000], ['price' => 999000]);
        $cart = app(Cart::class);
        $cart->add($a->id, 2);
        $cart->add($b->id);

        $a->product->update(['price' => 250000]); // giá đổi sau khi bỏ giỏ

        $this->assertSame(650000, $cart->subtotal());
        $this->assertSame(3, $cart->count());
    }

    public function test_lines_drop_deleted_and_hidden_but_flag_short_stock(): void
    {
        $gone = $this->variant(['stock' => 5]);
        $hidden = $this->variant(['stock' => 5]);
        $short = $this->variant(['stock' => 5]);
        $cart = app(Cart::class);
        foreach ([$gone, $hidden, $short] as $v) {
            $cart->add($v->id, 3);
        }

        $gone->delete();
        $hidden->product->update(['is_active' => false]);
        $short->update(['stock' => 1]);

        $lines = $cart->lines();
        $this->assertSame([$short->id], $lines->map(fn ($l) => $l->variant->id)->all());
        $this->assertFalse($lines->first()->isAvailable());
        $this->assertTrue($cart->hasUnavailable());
        $this->assertSame([$short->id => 3], $cart->items());
    }

    public function test_set_qty_zero_removes(): void
    {
        $v = $this->variant(['stock' => 5]);
        $cart = app(Cart::class);
        $cart->add($v->id);
        $cart->setQty($v->id, 0);

        $this->assertTrue($cart->isEmpty());
    }

    public function test_guest_cart_merges_into_account(): void
    {
        $v = $this->variant(['stock' => 10]);
        $w = $this->variant(['stock' => 10]);
        $customer = Customer::factory()->registered()->create(['cart' => [$v->id => 1]]);
        app(Cart::class)->add($v->id, 2);
        app(Cart::class)->add($w->id);

        $this->actingAs($customer);
        app(Cart::class)->mergeGuestCartInto($customer);

        $this->assertEquals([$v->id => 3, $w->id => 1], $customer->fresh()->cart);
        $this->assertEquals([$v->id => 3, $w->id => 1], app(Cart::class)->items());
        $this->assertNull(session('cart'));
    }
}
