@use('App\Support\Money')

<article class="rounded-card bg-surface p-5 shadow-soft sm:p-6">
    <header class="flex flex-wrap items-start justify-between gap-3 border-b border-line pb-4">
        <div>
            <p class="font-mono text-xl font-semibold tracking-wider">{{ $order->code }}</p>
            <p class="text-xs text-muted">Đặt lúc {{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->paymentLabel() }}</p>
        </div>
        <span class="rounded-full bg-surface-2 px-3 py-1 text-xs font-semibold">{{ $order->status->label() }}</span>
    </header>

    @if ($order->isAwaitingTransfer())
        <p class="mt-4 rounded-chip bg-accent/10 px-4 py-3 text-sm">
            Shop chưa nhận được chuyển khoản.
            <a class="font-semibold underline underline-offset-4" href="{{ url('/don-hang/'.$order->code.'/thanh-cong') }}">Xem mã QR</a>
        </p>
    @endif

    <ol class="mt-5 space-y-3 border-l border-line pl-5 text-sm">
        @foreach ($order->statusLogs as $log)
            <li class="relative">
                <span class="absolute top-1.5 -left-[25px] size-2.5 rounded-full {{ $loop->last ? 'bg-accent' : 'bg-line' }}"></span>
                <span class="font-semibold">{{ $log->to->label() }}</span>
                <span class="text-xs text-muted">· {{ $log->created_at->format('d/m/Y H:i') }}</span>
                @if ($log->note)
                    <p class="text-xs text-muted">{{ $log->note }}</p>
                @endif
            </li>
        @endforeach
    </ol>

    @if ($order->tracking_code)
        <p class="mt-4 text-sm">Mã vận đơn: <span class="font-mono font-semibold">{{ $order->tracking_code }}</span></p>
    @endif

    <ul class="mt-5 divide-y divide-line border-t border-line text-sm">
        @foreach ($order->items as $item)
            <li class="flex justify-between gap-4 py-3">
                <div>
                    <p class="font-medium">{{ $item->name }}</p>
                    <p class="text-xs text-muted">{{ collect([$item->color, $item->size])->filter()->implode(' / ') }} · {{ $item->qty }} × {{ Money::vnd($item->unit_price) }}</p>
                </div>
                <span class="tabular-nums">{{ Money::vnd($item->total()) }}</span>
            </li>
        @endforeach
    </ul>

    <dl class="space-y-1.5 border-t border-line pt-4 text-sm">
        <div class="flex justify-between"><dt class="text-muted">Tạm tính</dt><dd class="tabular-nums">{{ Money::vnd($order->subtotal) }}</dd></div>
        <div class="flex justify-between"><dt class="text-muted">Phí giao hàng</dt><dd class="tabular-nums">{{ $order->shipping_fee === 0 ? 'Miễn phí' : Money::vnd($order->shipping_fee) }}</dd></div>
        <div class="flex justify-between text-base font-semibold"><dt>Tổng cộng</dt><dd class="tabular-nums">{{ Money::vnd($order->total) }}</dd></div>
    </dl>

    <div class="mt-5 rounded-chip bg-surface-2 p-4 text-sm">
        <p class="font-semibold">{{ $order->customer_name }} · {{ $order->customer_phone }}</p>
        <p class="text-muted">{{ $order->addressLine() }}</p>
        @if ($order->note)
            <p class="mt-1 text-xs text-muted">Ghi chú: {{ $order->note }}</p>
        @endif
    </div>
</article>
