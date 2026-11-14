@extends('layouts.shop')
@use('App\Support\Money')
@use('App\Models\Order')

@section('title', 'Đặt hàng thành công')

@section('meta')
    <meta name="robots" content="noindex">
@endsection

@section('content')
    <div class="mx-auto max-w-xl px-4 pt-10 text-center lg:pt-16" data-reveal>
        <p class="eyebrow mb-2">Cảm ơn bạn</p>
        <h1 class="display text-4xl sm:text-5xl">Đặt hàng <em>thành công</em></h1>
        <p class="mt-4 text-muted">Mã đơn của bạn</p>
        <p class="mt-1 font-mono text-3xl font-semibold tracking-widest">{{ $order->code }}</p>

        <dl class="mx-auto mt-8 max-w-sm space-y-2 rounded-card bg-surface p-5 text-left text-sm shadow-soft">
            <div class="flex justify-between gap-4"><dt class="text-muted">Tổng tiền</dt><dd class="font-semibold tabular-nums">{{ Money::vnd($order->total) }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-muted">Thanh toán</dt><dd class="text-right">{{ $order->paymentLabel() }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-muted">Trạng thái</dt><dd>{{ $order->status->label() }}</dd></div>
        </dl>

        @if ($qr)
            <div class="mx-auto mt-8 max-w-sm rounded-card bg-surface p-5 shadow-soft">
                <p class="font-semibold">Quét mã để chuyển khoản</p>
                <img src="{{ $qr }}" alt="Mã VietQR chuyển khoản {{ Money::vnd($order->total) }} cho đơn {{ $order->code }}"
                    class="mx-auto mt-4 w-full max-w-[280px] rounded-chip bg-white" width="280" height="280">
                <dl class="mt-4 space-y-1 text-left text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-muted">Số tài khoản</dt><dd class="font-mono">{{ shop('bank_account_no') }}</dd></div>
                    @if (filled(shop('bank_account_name')))
                        <div class="flex justify-between gap-4"><dt class="text-muted">Chủ tài khoản</dt><dd>{{ shop('bank_account_name') }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-4"><dt class="text-muted">Nội dung chuyển khoản</dt><dd class="font-mono font-semibold">{{ $order->code }}</dd></div>
                </dl>
                <p class="mt-4 text-xs text-muted">Đơn tự huỷ nếu sau {{ Order::TRANSFER_EXPIRY_HOURS }} giờ shop chưa nhận được tiền.</p>
            </div>
        @elseif ($order->payment_method === 'cod')
            <p class="mt-8 text-sm text-muted">Shop sẽ gọi xác nhận và giao hàng sớm. Bạn trả tiền khi nhận hàng nhé.</p>
        @endif

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url('/tra-cuu-don') }}" class="btn btn-outline">Tra cứu đơn</a>
            <a href="{{ url('/san-pham') }}" class="btn btn-primary">Tiếp tục mua sắm</a>
        </div>
    </div>
@endsection
