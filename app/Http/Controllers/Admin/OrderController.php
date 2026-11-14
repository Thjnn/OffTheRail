<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Exceptions\OrderActionException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function show(Order $order): View
    {
        $order->load('items', 'statusLogs', 'staff', 'customer');

        return view('admin.orders.show', [
            'order' => $order,
            'next' => $this->orders->nextStatuses($order),
            'actors' => \App\Models\User::whereIn('id', $order->statusLogs->pluck('user_id')->filter())->pluck('name', 'id'),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'to' => ['required', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:255'],
            'tracking_code' => ['nullable', 'string', 'max:50'],
        ], [], ['to' => 'trạng thái mới']);

        try {
            $this->orders->transition($order, OrderStatus::from($data['to']), $request->user('admin'), $data['note'] ?? null, $data['tracking_code'] ?? null);
        } catch (OrderActionException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Đã cập nhật đơn.');
    }

    public function markPaid(Request $request, Order $order): RedirectResponse
    {
        try {
            $this->orders->markPaid($order, $request->user('admin'));
        } catch (OrderActionException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Đã ghi nhận thanh toán.');
    }

    public function print(Order $order): View
    {
        return view('admin.orders.print', ['order' => $order->load('items')]);
    }
}
