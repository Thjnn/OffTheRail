<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class WishlistButton extends Component
{
    #[Locked]
    public int $productId;

    public function toggle(): void
    {
        $customer = auth('web')->user();

        if ($customer === null) {
            session()->put('url.intended', url()->previous());
            $this->redirect('/tai-khoan/dang-nhap');

            return;
        }

        $customer->wishlist()->toggle([$this->productId]);
    }

    public function render(): View
    {
        $liked = (bool) auth('web')->user()?->wishlist()->whereKey($this->productId)->exists();

        return view('livewire.wishlist-button', ['liked' => $liked]);
    }
}
