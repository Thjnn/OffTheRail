<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\OrderFilters;
use App\Support\Spreadsheet;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $filters = $request->only(['status', 'channel', 'from', 'to', 'staff', 'q']);
        $orders = OrderFilters::apply(Order::with('staff'), array_map(fn ($v) => is_string($v) ? $v : null, $filters));

        $rows = (function () use ($orders) {
            foreach ($orders->lazyById(500) as $o) {
                yield [
                    $o->code,
                    $o->created_at->format('d/m/Y H:i'),
                    $o->channelLabel(),
                    $o->external_code,
                    $o->customer_name,
                    $o->customer_phone,
                    $o->status->label(),
                    $o->paymentLabel(),
                    match ($o->payment_status) { 'paid' => 'Có', 'refunded' => 'Đã hoàn', default => 'Chưa' },
                    $o->subtotal,
                    $o->discount,
                    $o->shipping_fee,
                    $o->total,
                    $o->staff?->name,
                    $o->tracking_code,
                ];
            }
        })();

        return Spreadsheet::download('don-hang-'.now()->format('Y-m-d').'.xlsx', [
            'Mã đơn', 'Ngày đặt', 'Kênh', 'Mã Shopee', 'Khách', 'SĐT', 'Trạng thái', 'Thanh toán', 'Đã trả',
            'Tạm tính', 'Giảm', 'Phí ship', 'Tổng', 'Nhân viên', 'Mã vận đơn',
        ], $rows);
    }
}
