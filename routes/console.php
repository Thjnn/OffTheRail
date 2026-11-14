<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Đơn chuyển khoản quá hạn mà chưa nhận được tiền: huỷ và hoàn kho.
Artisan::command('orders:cancel-expired', function (OrderService $orders) {
    $expired = Order::where('channel', 'web')
        ->where('payment_method', 'bank_transfer')
        ->where('payment_status', 'unpaid')
        ->where('status', OrderStatus::Pending)
        ->where('created_at', '<', now()->subHours(Order::TRANSFER_EXPIRY_HOURS))
        ->get();

    $count = $expired->filter(fn (Order $order) => $orders->cancel(
        $order, 'Quá '.Order::TRANSFER_EXPIRY_HOURS.' giờ chưa nhận được chuyển khoản'
    ))->count();

    $this->info("Đã huỷ {$count} đơn chuyển khoản quá hạn.");
})->purpose('Huỷ đơn chuyển khoản quá 24 giờ chưa thanh toán và hoàn kho');

Schedule::command('orders:cancel-expired')->hourly();
