<div>
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <div class="relative w-64">
            <x-admin.icon name="search" class="pointer-events-none absolute top-2 left-2.5 size-4 text-a-muted" />
            <input type="search" wire:model.live.debounce.400ms="q" class="a-input pl-8" placeholder="SKU, mã vạch, tên sản phẩm" aria-label="Tìm biến thể">
        </div>
        <label class="flex items-center gap-2"><input type="checkbox" wire:model.live="low"> Chỉ món sắp hết (≤ {{ \App\Models\ProductVariant::LOW_STOCK }})</label>
        @if ($isOwner)
            <a href="{{ url('/admin/kho/xuat-excel') }}" class="a-btn ml-auto"><x-admin.icon name="download" class="size-4" /> Xuất Excel tồn kho</a>
        @endif
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-md border border-a-up/30 bg-a-up/5 px-3 py-2 text-a-up" role="status">{{ session('status') }}</div>
    @endif

    <div class="a-card overflow-x-auto">
        <table class="a-table">
            <thead><tr><th>SKU</th><th>Mã vạch</th><th>Sản phẩm</th><th>Màu / Size</th><th class="text-right">Tồn</th><th></th></tr></thead>
            <tbody>
                @forelse ($variants as $v)
                    <tr wire:key="v{{ $v->id }}">
                        <td class="font-mono text-xs">{{ $v->sku }}</td>
                        <td class="font-mono text-xs text-a-muted">{{ $v->barcode }}</td>
                        <td>{{ $v->product->name }}</td>
                        <td class="text-a-muted">{{ $v->color?->name }} / {{ $v->size }}</td>
                        <td class="text-right font-medium {{ $v->stock <= \App\Models\ProductVariant::LOW_STOCK ? 'text-a-down' : '' }}">{{ $v->stock }}</td>
                        <td class="text-right whitespace-nowrap">
                            <button type="button" wire:click="showHistory({{ $v->id }})" class="text-a-muted hover:text-a-ink">Lịch sử</button>
                            @if ($isOwner)
                                <button type="button" wire:click="openAdjust({{ $v->id }})" class="ml-3 text-accent hover:underline">Điều chỉnh</button>
                            @endif
                        </td>
                    </tr>

                    @if ($adjustingId === $v->id)
                        <tr wire:key="adj{{ $v->id }}">
                            <td colspan="6" class="bg-a-soft">
                                <form wire:submit="adjust" class="flex flex-wrap items-start gap-2">
                                    <select wire:model.live="mode" class="a-input w-40" aria-label="Kiểu điều chỉnh">
                                        <option value="import">Nhập thêm</option>
                                        <option value="count">Đếm lại (đặt số tồn)</option>
                                    </select>
                                    <div>
                                        <input type="number" wire:model="qty" min="{{ $mode === 'import' ? 1 : 0 }}" class="a-input w-28"
                                            placeholder="{{ $mode === 'import' ? 'Số nhập' : 'Số đếm được' }}" aria-label="Số lượng">
                                        @error('qty')<p class="a-error">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="min-w-60 flex-1">
                                        <input wire:model="note" maxlength="200" class="a-input" placeholder="Lý do, vd. Nhập lô tháng 11 / Kiểm kho cuối tháng" aria-label="Lý do">
                                        @error('note')<p class="a-error">{{ $message }}</p>@enderror
                                    </div>
                                    <button class="a-btn a-btn-primary">Lưu</button>
                                    <button type="button" wire:click="$set('adjustingId', null)" class="a-btn">Huỷ</button>
                                </form>
                            </td>
                        </tr>
                    @endif

                    @if ($historyId === $v->id)
                        <tr wire:key="his{{ $v->id }}">
                            <td colspan="6" class="bg-a-soft">
                                @forelse ($history as $m)
                                    <div class="flex flex-wrap gap-x-4 py-0.5 text-xs">
                                        <span class="w-28 text-a-muted">{{ $m->created_at->format('d/m/Y H:i') }}</span>
                                        <span class="w-10 text-right font-medium {{ $m->change > 0 ? 'text-a-up' : 'text-a-down' }}">{{ $m->change > 0 ? '+' : '' }}{{ $m->change }}</span>
                                        <span class="w-20">{{ $m->reasonLabel() }}</span>
                                        @if ($m->order)<a href="{{ url('/admin/don-hang/'.$m->order->code) }}" class="font-mono text-accent">{{ $m->order->code }}</a>@endif
                                        <span class="text-a-muted">{{ $m->user?->name }}</span>
                                        @if ($m->note)<span>{{ $m->note }}</span>@endif
                                    </div>
                                @empty
                                    <p class="text-xs text-a-muted">Chưa có biến động kho.</p>
                                @endforelse
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-a-muted">Không có biến thể nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $variants->links() }}</div>
</div>
