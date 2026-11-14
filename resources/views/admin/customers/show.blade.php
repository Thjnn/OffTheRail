@extends('layouts.admin')

@section('title', $customer->name)

@section('actions')
    <a href="{{ url('/admin/khach-hang') }}" class="a-btn">← Danh sách</a>
@endsection

@section('content')
    <div class="grid gap-5 lg:grid-cols-[320px_1fr]">
        <aside class="space-y-5">
            <section class="a-card space-y-1 p-4">
                <p class="font-mono">{{ $customer->phone }}</p>
                @if ($customer->email)<p class="text-a-muted">{{ $customer->email }}</p>@endif
                <p class="text-a-muted">{{ $customer->hasAccount() ? 'Có tài khoản từ '.$customer->registered_at?->format('d/m/Y') : 'Khách vãng lai (chưa đăng ký)' }}</p>
                <div class="grid grid-cols-2 gap-2 pt-3">
                    <div><p class="text-xs text-a-muted">Số đơn</p><p class="text-lg font-semibold">{{ $customer->orders_count }}</p></div>
                    <div><p class="text-xs text-a-muted">Tổng chi tiêu</p><p class="text-lg font-semibold">{{ \App\Support\Money::vnd($customer->total_spent) }}</p></div>
                </div>
            </section>

            <section class="a-card p-4">
                <h2 class="mb-2 font-semibold">Địa chỉ đã lưu</h2>
                @forelse ($customer->addresses as $address)
                    <p class="py-1">{{ $address->name }} · {{ $address->phone }}<br><span class="text-a-muted">{{ $address->line() }}</span></p>
                @empty
                    <p class="text-a-muted">Chưa lưu địa chỉ nào.</p>
                @endforelse
            </section>
        </aside>

        <section class="a-card overflow-x-auto">
            <h2 class="px-4 pt-4 pb-2 font-semibold">Tất cả đơn ({{ $orders->count() }})</h2>
            <table class="a-table">
                <thead><tr><th>Mã đơn</th><th>Ngày</th><th>Kênh</th><th class="text-right">Tổng</th><th>Thanh toán</th><th>Trạng thái</th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a href="{{ url('/admin/don-hang/'.$order->code) }}" class="font-mono text-accent hover:underline">{{ $order->code }}</a></td>
                            <td class="text-a-muted">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $order->channelLabel() }}</td>
                            <td class="text-right">{{ \App\Support\Money::vnd($order->total) }}</td>
                            <td>{{ $order->paymentStatusLabel() }}</td>
                            <td><span class="a-badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-a-muted">Chưa có đơn nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endsection
