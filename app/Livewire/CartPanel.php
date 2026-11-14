<?php

namespace App\Livewire;

use App\Support\Cart;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Giỏ hàng, dùng ở hai chỗ: ngăn kéo trượt trong layout (drawer = true) và trang /gio-hang.
 * Lắng nghe event `add-to-cart` do VariantPicker phát.
 */
class CartPanel extends Component
{
    public bool $drawer = false;

    public bool $open = false;

    public ?string $notice = null;

    #[On('add-to-cart')]
    public function addToCart($variantId = null, $buyNow = false): void
    {
        $this->notice = null;

        if (! app(Cart::class)->add(is_scalar($variantId) ? (int) $variantId : 0)) {
            $this->notice = 'Sản phẩm này vừa hết hàng.';
            $this->open = true;

            return;
        }

        $this->changed();

        if ($buyNow) {
            $this->redirect('/thanh-toan');

            return;
        }

        $this->open = true;
    }

    #[On('open-cart')]
    public function show(): void
    {
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function setQty($variantId, $qty): void
    {
        app(Cart::class)->setQty((int) $variantId, (int) $qty);
        $this->changed();
    }

    public function remove($variantId): void
    {
        app(Cart::class)->remove((int) $variantId);
        $this->changed();
    }

    public function render(): View
    {
        $lines = app(Cart::class)->lines();
        $subtotal = (int) $lines->sum(fn ($line) => $line->total());
        $missing = (int) shop('free_shipping_threshold') - $subtotal;

        return view('livewire.cart-panel', [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'freeShipHint' => $missing > 0 && $lines->isNotEmpty() ? 'Mua thêm '.Money::vnd($missing).' để được freeship' : null,
            'blocked' => $lines->contains(fn ($line) => ! $line->isAvailable()),
        ]);
    }

    private function changed(): void
    {
        $this->dispatch('cart-updated', count: app(Cart::class)->count());
    }
}
