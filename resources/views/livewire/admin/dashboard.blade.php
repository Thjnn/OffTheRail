<div>
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xl font-semibold">{{ $greeting }}</p>
            <p class="text-a-muted">{{ shop('name') }} · {{ now()->format('d/m/Y') }}</p>
        </div>
        <select wire:model.live="range" class="a-input w-40" aria-label="Khoảng thời gian">
            @foreach (\App\Livewire\Admin\Dashboard::RANGES as $days => $label)
                <option value="{{ $days }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    @php
        $delta = function (?int $pct) {
            if ($pct === null) return '';
            $cls = $pct >= 0 ? 'text-a-up' : 'text-a-down';
            return '<span class="'.$cls.' text-xs font-medium">'.($pct >= 0 ? '▲ ' : '▼ ').abs($pct).'%</span>';
        };
    @endphp

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="a-card p-4">
            <p class="text-a-muted">{{ $user->isOwner() ? 'Doanh thu' : 'Doanh số của bạn' }}</p>
            <p class="mt-1 text-2xl font-semibold">{{ \App\Support\Money::vnd($revenue) }}</p>
            {!! $delta($revenueChange) !!} <span class="text-xs text-a-muted">so với kỳ trước</span>
        </div>
        <div class="a-card p-4">
            <p class="text-a-muted">Đơn hàng</p>
            <p class="mt-1 text-2xl font-semibold">{{ $orders }}</p>
            {!! $delta($ordersChange) !!} <span class="text-xs text-a-muted">so với kỳ trước</span>
        </div>
        <a href="{{ url('/admin/don-hang?status=pending') }}" class="a-card block p-4 hover:border-accent">
            <p class="text-a-muted">Chờ xử lý</p>
            <p class="mt-1 text-2xl font-semibold {{ $pending ? 'text-a-down' : '' }}">{{ $pending }}</p>
            <span class="text-xs text-a-muted">đơn web chưa xác nhận</span>
        </a>
        <a href="{{ url('/admin/kho?low=1') }}" class="a-card block p-4 hover:border-accent">
            <p class="text-a-muted">Sắp hết</p>
            <p class="mt-1 text-2xl font-semibold">{{ $lowStockCount }}</p>
            <span class="text-xs text-a-muted">biến thể còn ≤ {{ \App\Models\ProductVariant::LOW_STOCK }}</span>
        </a>
    </div>

    <section class="a-card mt-5 p-4">
        <h2 class="mb-2 font-semibold">Doanh thu theo kênh</h2>
        <div wire:key="chart-{{ $range }}-{{ md5(json_encode($chart)) }}" wire:ignore data-chart='@json($chart)' class="h-72" role="img"
            aria-label="Biểu đồ cột doanh thu theo ngày của các kênh Web, Tại quầy, Shopee"></div>
    </section>

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <section class="a-card overflow-hidden">
            <h2 class="px-4 pt-4 pb-2 font-semibold">Đơn mới nhất</h2>
            <table class="a-table">
                <tbody>
                    @forelse ($latest as $order)
                        <tr>
                            <td><a href="{{ url('/admin/don-hang/'.$order->code) }}" class="font-mono text-accent hover:underline">{{ $order->code }}</a></td>
                            <td>{{ $order->customer_name }}</td>
                            <td class="text-a-muted">{{ $order->channelLabel() }}</td>
                            <td class="text-right">{{ \App\Support\Money::vnd($order->total) }}</td>
                            <td><span class="a-badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="py-6 text-center text-a-muted">Chưa có đơn nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="a-card overflow-hidden">
            <h2 class="px-4 pt-4 pb-2 font-semibold">Sắp hết hàng</h2>
            <table class="a-table">
                <tbody>
                    @forelse ($lowStock as $v)
                        <tr>
                            <td>{{ $v->product->name }}</td>
                            <td class="text-a-muted">{{ $v->color?->name }} / {{ $v->size }}</td>
                            <td class="font-mono text-xs">{{ $v->sku }}</td>
                            <td class="text-right font-medium {{ $v->stock === 0 ? 'text-a-down' : '' }}">{{ $v->stock }}</td>
                        </tr>
                    @empty
                        <tr><td class="py-6 text-center text-a-muted">Kho ổn, chưa có món nào sắp hết.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</div>
