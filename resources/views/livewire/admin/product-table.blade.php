<div>
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <div class="relative w-64">
            <x-admin.icon name="search" class="pointer-events-none absolute top-2 left-2.5 size-4 text-a-muted" />
            <input type="search" wire:model.live.debounce.400ms="q" class="a-input pl-8" placeholder="Tên, SKU, mã vạch" aria-label="Tìm sản phẩm">
        </div>
        <select wire:model.live="category" class="a-input w-40" aria-label="Danh mục">
            <option value="">Mọi danh mục</option>
            @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
        </select>
        <select wire:model.live="active" class="a-input w-36" aria-label="Trạng thái">
            <option value="">Mọi trạng thái</option>
            <option value="1">Đang bán</option>
            <option value="0">Đang ẩn</option>
        </select>
        @if ($isOwner)
            <a href="{{ url('/admin/san-pham/tao') }}" class="a-btn a-btn-primary ml-auto"><x-admin.icon name="plus" class="size-4" /> Thêm sản phẩm</a>
        @endif
    </div>

    <div class="a-card overflow-x-auto">
        <table class="a-table">
            <thead><tr><th class="w-14"></th><th>Sản phẩm</th><th>Danh mục</th><th class="text-right">Giá</th><th class="text-right">Tồn</th><th>Trạng thái</th><th></th></tr></thead>
            <tbody>
                @forelse ($products as $p)
                    <tr wire:key="p{{ $p->id }}">
                        <td>
                            @if ($url = $p->coverImageUrl())
                                <img src="{{ $url }}" alt="" class="size-10 rounded object-cover" loading="lazy">
                            @else
                                <div class="size-10 rounded bg-a-soft"></div>
                            @endif
                        </td>
                        <td class="font-medium">{{ $p->name }}</td>
                        <td class="text-a-muted">{{ $p->category?->name }}</td>
                        <td class="text-right whitespace-nowrap">{{ \App\Support\Money::vnd($p->price) }}</td>
                        <td class="text-right {{ (int) $p->stock_total === 0 ? 'text-a-down' : '' }}">{{ (int) $p->stock_total }}</td>
                        <td>
                            @if ($p->is_active)<span class="a-badge bg-green-100 text-green-800">Đang bán</span>@else<span class="a-badge bg-a-soft text-a-muted">Đang ẩn</span>@endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ url('/admin/san-pham/'.$p->id.'/in-tem') }}" class="text-accent hover:underline">In tem</a>
                            @if ($isOwner)
                                <a href="{{ url('/admin/san-pham/'.$p->id.'/sua') }}" class="ml-3 text-accent hover:underline">Sửa</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-a-muted">Không có sản phẩm nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
</div>
