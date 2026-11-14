<div>
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <div class="relative w-64">
            <x-admin.icon name="search" class="pointer-events-none absolute top-2 left-2.5 size-4 text-a-muted" />
            <input type="search" wire:model.live.debounce.400ms="q" class="a-input pl-8" placeholder="SĐT hoặc tên" aria-label="Tìm khách">
        </div>
        <select wire:model.live="sort" class="a-input w-48" aria-label="Sắp xếp">
            @foreach (\App\Livewire\Admin\CustomerTable::SORTS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
    </div>

    <div class="a-card overflow-x-auto">
        <table class="a-table">
            <thead><tr><th>Khách</th><th>SĐT</th><th>Email</th><th>Tài khoản</th><th class="text-right">Số đơn</th><th class="text-right">Tổng chi tiêu</th><th>Ngày tạo</th></tr></thead>
            <tbody>
                @forelse ($customers as $c)
                    <tr wire:key="c{{ $c->id }}">
                        <td><a href="{{ url('/admin/khach-hang/'.$c->id) }}" class="font-medium text-accent hover:underline">{{ $c->name }}</a></td>
                        <td class="font-mono text-xs">{{ $c->phone }}</td>
                        <td class="text-a-muted">{{ $c->email }}</td>
                        <td>{{ $c->hasAccount() ? 'Có' : '—' }}</td>
                        <td class="text-right">{{ $c->orders_count }}</td>
                        <td class="text-right font-medium">{{ \App\Support\Money::vnd($c->total_spent) }}</td>
                        <td class="text-a-muted">{{ $c->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-a-muted">Không tìm thấy khách nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $customers->links() }}</div>
</div>
