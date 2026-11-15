@php($money = fn ($v) => \App\Support\Money::vnd($v))
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Hoá đơn {{ $order->code }}</title>
    <style>
        @page { size: 80mm auto; margin: 3mm; }
        * { box-sizing: border-box; }
        body { font: 12px/1.4 ui-monospace, Consolas, "Courier New", monospace; color: #000; margin: 0; width: 74mm; }
        h1 { font-size: 15px; margin: 0; text-align: center; }
        .c { text-align: center; }
        .r { text-align: right; }
        .row { display: flex; justify-content: space-between; gap: 6px; }
        .sep { border-top: 1px dashed #000; margin: 6px 0; }
        .item { margin-bottom: 4px; }
        .total { font-size: 15px; font-weight: 700; }
        .actions { display: flex; gap: 8px; justify-content: center; margin: 12px 0; font-family: system-ui, sans-serif; }
        .actions a, .actions button { font: inherit; font-size: 14px; padding: 8px 14px; border: 1px solid #999; border-radius: 6px; background: #fff; color: #000; text-decoration: none; cursor: pointer; }
        @media screen { body { margin: 8mm auto; } }
        @media print { .actions { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <h1>{{ shop('name') }}</h1>
    @if (shop('hotline'))<div class="c">Hotline: {{ shop('hotline') }}</div>@endif

    <div class="sep"></div>
    <div class="row"><span>Hoá đơn</span><strong>{{ $order->code }}</strong></div>
    <div class="row"><span>Lúc</span><span>{{ $order->created_at->format('H:i d/m/Y') }}</span></div>
    @if ($order->staff)<div class="row"><span>Thu ngân</span><span>{{ $order->staff->name }}</span></div>@endif
    @if ($order->customer_phone)<div class="row"><span>Khách</span><span>{{ $order->customer_name }} · {{ $order->customer_phone }}</span></div>@endif

    <div class="sep"></div>
    @foreach ($order->items as $item)
        <div class="item">
            <div>{{ $item->name }}</div>
            <div class="row">
                <span>{{ collect([$item->color, $item->size])->filter()->join(' / ') }} · {{ $item->qty }} × {{ $money($item->unit_price) }}</span>
                <span>{{ $money($item->unit_price * $item->qty) }}</span>
            </div>
        </div>
    @endforeach

    <div class="sep"></div>
    <div class="row"><span>Tạm tính</span><span>{{ $money($order->subtotal) }}</span></div>
    @if ($order->discount > 0)<div class="row"><span>Giảm giá</span><span>−{{ $money($order->discount) }}</span></div>@endif
    <div class="row total"><span>Tổng</span><span>{{ $money($order->total) }}</span></div>
    @if ($order->payment_method === 'cash' && $order->cash_received !== null)
        <div class="row"><span>Khách đưa</span><span>{{ $money($order->cash_received) }}</span></div>
        <div class="row"><span>Tiền thối</span><span>{{ $money($order->cash_received - $order->total) }}</span></div>
    @else
        <div class="row"><span>Thanh toán</span><span>{{ $order->paymentLabel() }}</span></div>
    @endif
    @if ($order->payment_status === 'refunded')<div class="c"><strong>ĐÃ HOÀN TIỀN</strong></div>@endif

    <div class="sep"></div>
    <div class="c">Cảm ơn quý khách!</div>
    @foreach ((array) shop('policy_lines') as $line)<div class="c">{{ $line }}</div>@endforeach

    <div class="actions">
        <button type="button" onclick="window.print()">In lại</button>
        <a href="{{ url('/admin/ban-tai-quay') }}">Đơn mới</a>
    </div>
</body>
</html>
