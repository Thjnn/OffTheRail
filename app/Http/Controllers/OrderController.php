<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\VietQr;
use Illuminate\Contracts\View\View;

class OrderController extends Controller
{
    /** Trang đặt xong. Ai có mã đơn cũng mở được, nên chỉ hiện thông tin không nhạy cảm. */
    public function success(Order $order): View
    {
        abort_unless($order->channel === 'web', 404);

        return view('orders.success', [
            'order' => $order,
            'qr' => $order->isAwaitingTransfer() ? VietQr::url($order) : null,
        ]);
    }
}
