<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Contracts\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        return view('account.index', ['orders' => $this->customer()->visibleOrders()->paginate(10)]);
    }

    public function order(string $code): View
    {
        $order = $this->customer()->visibleOrders()->with('items', 'statusLogs')->where('code', $code)->firstOrFail();

        return view('account.order', ['order' => $order]);
    }

    public function wishlist(): View
    {
        $products = $this->customer()->wishlist()->visible()
            ->with('images', 'variants')->latest('wishlists.created_at')->get();

        return view('account.wishlist', ['products' => $products]);
    }

    private function customer(): Customer
    {
        return auth('web')->user();
    }
}
