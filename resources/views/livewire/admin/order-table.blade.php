<div>
    <div class="mb-4 flex flex-wrap items-end gap-2">
        <div class="relative w-64">
            <x-admin.icon name="search" class="pointer-events-none absolute top-2 left-2.5 size-4 text-a-muted" />
            <input type="search" wire:model.live.debounce.400ms="q" class="a-input pl-8" placeholder="Mã đơn, SĐT, tên khách" aria-label="Tìm đơn">
        </div>
        <select wire:model.live="status" class="a-input w-40" aria-label="Trạng thái">
            <option value="">Mọi trạng thái</option>
            @foreach ($statuses as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
        </select>
        <select wire:model.live="channel" class="a-input w-32" aria-label="Kênh">
            <option value="">Mọi kênh</option>
            @foreach (\App\Models\Order::CHANNELS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
        <select wire:model.live="staff" class="a-input w-40" aria-label="Nhân viên">
            <option value="">Mọi nhân viên</option>
            @foreach ($staffList as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
        </select>
        <input type="date" wire:model.live="from" class="a-input w-36" aria-label="Từ ngày">
        <input type="date" wire:model.live="to" class="a-input w-36" aria-label="Đến ngày">

        <div class="ml-auto flex gap-2">
            <a href="{{ url('/admin/don-hang/shopee') }}" class="a-btn"><x-admin.icon name="plus" class="size-4" /> Nhập đơn Shopee</a>
            @if ($isOwner)
                <a href="{{ url('/admin/don-hang/xuat-excel').($this->filters() ? '?'.http_build_query($this->filters()) : '') }}" class="a-btn">
                    <x-admin.icon name="download" class="size-4" /> Xuất Excel
                </a>
            @endif
        </div>
    </div>

    <div class="a-card overflow-x-auto">
        <table class="a-table">
            <thead>
                <tr><th>Mã đơn</th><th>Thời gian</th><th>Kênh</th><th>Khách</th><th class="text-right">Tổng</th><th>Thanh toán</th><th>Trạng thái</th></tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr wire:key="o{{ $order->id }}">
                        <td><a href="{{ url('/admin/don-hang/'.$order->code) }}" class="font-mono font-medium text-accent hover:underline">{{ $order->code }}</a></td>
                        <td class="whitespace-nowrap text-a-muted">{{ $order->created_at->format('d/m H:i') }}</td>
                        <td>{{ $order->channelLabel() }}@if ($order->external_code) <span class="text-xs text-a-muted">· {{ $order->external_code }}</span>@endif</td>
                        <td>{{ $order->customer_name }} <span class="text-a-muted">{{ $order->customer_phone }}</span></td>
                        <td class="text-right font-medium whitespace-nowrap">{{ \App\Support\Money::vnd($order->total) }}</td>
                        <td class="whitespace-nowrap {{ $order->payment_status === 'paid' ? 'text-a-up' : 'text-a-muted' }}">{{ $order->paymentStatusLabel() }}</td>
                        <td><span class="a-badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-a-muted">Không có đơn nào khớp bộ lọc.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
</div>
