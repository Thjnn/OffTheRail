@php($money = fn ($v) => \App\Support\Money::vnd($v))
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <title>Phiếu giao hàng {{ $order->code }}</title>
    <style>
        @page { size: A5; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font: 12px/1.45 system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: #111; margin: 0; }
        h1 { font-size: 16px; margin: 0; }
        .row { display: flex; justify-content: space-between; gap: 12px; }
        .muted { color: #555; }
        .box { border: 1px solid #999; border-radius: 4px; padding: 8px; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border-bottom: 1px solid #ccc; padding: 4px 2px; text-align: left; }
        .r { text-align: right; }
        .code { font: 700 18px ui-monospace, Consolas, monospace; letter-spacing: 1px; }
        .cod { font-size: 16px; font-weight: 700; margin-top: 10px; border: 2px solid #111; padding: 6px; text-align: center; }
        @media screen { body { max-width: 148mm; margin: 10mm auto; } }
    </style>
</head>
<body onload="window.print()">
    <div class="row">
        <div>
            <h1>{{ shop('name') }}</h1>
            @if (shop('hotline'))<div class="muted">Hotline: {{ shop('hotline') }}</div>@endif
        </div>
        <div class="r">
            <div class="code">{{ $order->code }}</div>
            <div class="muted">{{ $order->created_at->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="box">
        <strong>Người nhận:</strong> {{ $order->customer_name }}<br>
        @if ($order->customer_phone)<strong>SĐT:</strong> {{ $order->customer_phone }}<br>@endif
        @if ($order->address)<strong>Địa chỉ:</strong> {{ $order->addressLine() }}<br>@endif
        @if ($order->note)<strong>Ghi chú:</strong> {{ $order->note }}@endif
    </div>

    <table>
        <thead><tr><th>Món</th><th class="r">SL</th><th class="r">Thành tiền</th></tr></thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->name }} ({{ implode(' / ', array_filter([$item->color, $item->size])) }})</td>
                    <td class="r">{{ $item->qty }}</td>
                    <td class="r">{{ $money($item->total()) }}</td>
                </tr>
            @endforeach
            <tr><td colspan="2" class="r">Phí ship</td><td class="r">{{ $money($order->shipping_fee) }}</td></tr>
            @if ($order->discount)<tr><td colspan="2" class="r">Giảm</td><td class="r">−{{ $money($order->discount) }}</td></tr>@endif
            <tr><td colspan="2" class="r"><strong>Tổng</strong></td><td class="r"><strong>{{ $money($order->total) }}</strong></td></tr>
        </tbody>
    </table>

    @if ($order->payment_status === 'unpaid')
        <div class="cod">Thu hộ: {{ $money($order->total) }}</div>
    @else
        <div class="cod">Đã thanh toán, không thu tiền</div>
    @endif
</body>
</html>
