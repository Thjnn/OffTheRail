<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Phone;
use App\Support\VietQr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function lookupForm(): View
    {
        return view('orders.lookup', ['order' => null]);
    }

    /**
     * Tra đơn bằng mã + SĐT. Mọi trường hợp không khớp trả cùng một câu, để không dò được mã nào có thật.
     * Kết quả render thẳng (không redirect) nên SĐT không nằm trên URL.
     */
    public function lookup(Request $request): View|RedirectResponse
    {
        $code = strtoupper(trim((string) $request->input('code')));
        $phone = Phone::normalize((string) $request->input('phone'));

        $order = $code !== '' && $phone !== null
            ? Order::with('items', 'statusLogs')->where('channel', 'web')->where('code', $code)->where('customer_phone', $phone)->first()
            : null;

        if ($order === null) {
            return back()->withInput()->withErrors(['code' => 'Không tìm thấy đơn với mã và số điện thoại này.']);
        }

        return view('orders.lookup', ['order' => $order]);
    }

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
