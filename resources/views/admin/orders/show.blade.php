@extends('layouts.admin')

@section('title', 'Đơn '.$order->code)

@section('actions')
    <a href="{{ url('/admin/don-hang') }}" class="a-btn">← Danh sách</a>
    @if ($order->channel === 'pos')
        <a href="{{ url('/admin/don-hang/'.$order->code.'/hoa-don') }}" target="_blank" class="a-btn"><x-admin.icon name="print" class="size-4" /> In hoá đơn</a>
    @else
        <a href="{{ url('/admin/don-hang/'.$order->code.'/in') }}" target="_blank" class="a-btn"><x-admin.icon name="print" class="size-4" /> In phiếu</a>
    @endif
@endsection

@section('content')
    @php($money = fn ($v) => \App\Support\Money::vnd($v))
    <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
        <div class="space-y-5">
            <section class="a-card p-4">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <span class="a-badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                    <span class="a-badge bg-a-soft text-a-muted">{{ $order->channelLabel() }}</span>
                    <span class="a-badge {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-a-soft text-a-muted' }}">{{ $order->paymentLabel() }} · {{ $order->paymentStatusLabel() }}</span>
                    <span class="ml-auto text-a-muted">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                </div>

                <table class="a-table">
                    <thead><tr><th>Món</th><th>SKU</th><th class="text-right">Đơn giá</th><th class="text-right">SL</th><th class="text-right">Thành tiền</th></tr></thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>{{ $item->name }} <span class="text-a-muted">· {{ implode(' / ', array_filter([$item->color, $item->size])) }}</span></td>
                                <td class="font-mono text-xs">{{ $item->sku }}</td>
                                <td class="text-right">{{ $money($item->unit_price) }}</td>
                                <td class="text-right">{{ $item->qty }}</td>
                                <td class="text-right">{{ $money($item->total()) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <dl class="mt-3 ml-auto max-w-xs space-y-1">
                    <div class="flex justify-between"><dt class="text-a-muted">Tạm tính</dt><dd>{{ $money($order->subtotal) }}</dd></div>
                    @if ($order->discount)<div class="flex justify-between"><dt class="text-a-muted">Giảm</dt><dd>−{{ $money($order->discount) }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-a-muted">Phí ship</dt><dd>{{ $money($order->shipping_fee) }}</dd></div>
                    <div class="flex justify-between border-t border-a-line pt-1 text-base font-semibold"><dt>Tổng</dt><dd>{{ $money($order->total) }}</dd></div>
                </dl>
            </section>

            <section class="a-card p-4">
                <h2 class="mb-3 font-semibold">Lịch sử</h2>
                <ol class="space-y-2">
                    @foreach ($order->statusLogs as $log)
                        <li class="flex flex-wrap gap-x-3">
                            <span class="w-32 text-a-muted">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                            <span class="font-medium">{{ $log->to->label() }}</span>
                            <span class="text-a-muted">{{ $log->user_id ? ($actors[$log->user_id] ?? 'Nhân sự đã xoá') : 'Hệ thống / khách' }}</span>
                            @if ($log->note)<span>· {{ $log->note }}</span>@endif
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="a-card p-4">
                <h2 class="mb-2 font-semibold">Khách hàng</h2>
                <p>{{ $order->customer_name }}</p>
                @if ($order->customer_phone)<p class="text-a-muted">{{ $order->customer_phone }}</p>@endif
                @if ($order->customer_email)<p class="text-a-muted">{{ $order->customer_email }}</p>@endif
                @if ($order->address)<p class="mt-2">{{ $order->addressLine() }}</p>@endif
                @if ($order->note)<p class="mt-2 rounded bg-amber-50 p-2 text-amber-900">Ghi chú: {{ $order->note }}</p>@endif
                @if ($order->external_code)<p class="mt-2">Mã Shopee: <span class="font-mono">{{ $order->external_code }}</span></p>@endif
                @if ($order->tracking_code)<p class="mt-2">Mã vận đơn: <span class="font-mono">{{ $order->tracking_code }}</span></p>@endif
                @if ($order->staff)<p class="mt-2 text-a-muted">Nhân viên: {{ $order->staff->name }}</p>@endif
            </section>

            @if ($next || ($order->payment_status === 'unpaid' && ! in_array($order->status->value, ['cancelled', 'returned'])))
                <section class="a-card space-y-3 p-4">
                    <h2 class="font-semibold">Xử lý đơn</h2>

                    @if ($order->payment_status === 'unpaid' && ! in_array($order->status->value, ['cancelled', 'returned']))
                        <form method="POST" action="{{ url('/admin/don-hang/'.$order->code.'/da-thanh-toan') }}">
                            @csrf
                            <button class="a-btn w-full">Đã nhận tiền</button>
                        </form>
                    @endif

                    @foreach ($next as $to)
                        @php($destructive = in_array($to->value, ['cancelled', 'returned']))
                        <form method="POST" action="{{ url('/admin/don-hang/'.$order->code.'/trang-thai') }}" class="space-y-2"
                            @if ($destructive) onsubmit="return confirm('{{ $to->actionLabel() }} {{ $order->code }}? Hàng sẽ được cộng lại vào kho.')" @endif>
                            @csrf
                            <input type="hidden" name="to" value="{{ $to->value }}">
                            @if ($to->value === 'shipping')
                                <input name="tracking_code" class="a-input" placeholder="Mã vận đơn" required maxlength="50" aria-label="Mã vận đơn">
                            @endif
                            @if ($destructive)
                                <input name="note" class="a-input" placeholder="Lý do (tuỳ chọn)" maxlength="255" aria-label="Lý do">
                            @endif
                            <button class="a-btn w-full {{ $destructive ? 'a-btn-danger' : 'a-btn-primary' }}">{{ $to->actionLabel() }}</button>
                        </form>
                    @endforeach
                </section>
            @endif
        </aside>
    </div>
@endsection
