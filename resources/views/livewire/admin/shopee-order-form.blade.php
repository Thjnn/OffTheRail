@php($money = fn ($v) => \App\Support\Money::vnd((int) $v))
<form wire:submit="save" class="grid max-w-5xl gap-5 lg:grid-cols-[1fr_320px]">
    <section class="a-card p-4">
        <label class="a-label" for="search">Tìm món (SKU, mã vạch, tên sản phẩm)</label>
        <div class="relative">
            <x-admin.icon name="search" class="pointer-events-none absolute top-2 left-2.5 size-4 text-a-muted" />
            <input id="search" type="search" wire:model.live.debounce.300ms="search" class="a-input pl-8" autocomplete="off">
        </div>

        @if ($results->isNotEmpty())
            <ul class="mt-2 divide-y divide-a-line rounded-md border border-a-line">
                @foreach ($results as $v)
                    <li wire:key="r{{ $v->id }}">
                        <button type="button" wire:click="add({{ $v->id }})" class="flex w-full items-center gap-3 px-3 py-2 text-left hover:bg-a-soft">
                            <span class="flex-1">{{ $v->product->name }} <span class="text-a-muted">· {{ $v->color?->name }} / {{ $v->size }}</span></span>
                            <span class="font-mono text-xs text-a-muted">{{ $v->sku }}</span>
                            <span class="w-16 text-right {{ $v->stock > 0 ? '' : 'text-a-down' }}">Tồn {{ $v->stock }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        @elseif (mb_strlen(trim($search)) >= 2)
            <p class="mt-2 text-a-muted">Không tìm thấy món nào.</p>
        @endif

        <h2 class="mt-5 mb-2 font-semibold">Các món trong đơn</h2>
        @error('lines')<p class="a-error mb-2">{{ $message }}</p>@enderror
        <table class="a-table">
            <thead><tr><th>Món</th><th class="text-right">Giá web</th><th class="text-center">SL</th><th></th></tr></thead>
            <tbody>
                @forelse ($lines as $id => $qty)
                    @php($v = $selected->get($id))
                    @continue(! $v)
                    <tr wire:key="l{{ $id }}">
                        <td>{{ $v->product->name }} <span class="text-a-muted">· {{ $v->color?->name }} / {{ $v->size }}</span></td>
                        <td class="text-right">{{ $money($v->price ?? $v->product->price) }}</td>
                        <td class="text-center whitespace-nowrap">
                            <button type="button" class="a-btn px-2 py-0.5" wire:click="decrease({{ $id }})" aria-label="Bớt">−</button>
                            <span class="mx-2">{{ $qty }}</span>
                            <button type="button" class="a-btn px-2 py-0.5" wire:click="add({{ $id }})" aria-label="Thêm">+</button>
                        </td>
                        <td class="text-right"><button type="button" class="text-a-muted hover:text-a-down" wire:click="remove({{ $id }})" aria-label="Xoá món"><x-admin.icon name="trash" class="size-4" /></button></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-6 text-center text-a-muted">Chưa có món nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <aside class="a-card h-fit space-y-3 p-4">
        <div>
            <label class="a-label" for="externalCode">Mã đơn Shopee</label>
            <input id="externalCode" wire:model="externalCode" class="a-input font-mono uppercase" maxlength="40" autocomplete="off">
            @error('externalCode')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-between text-a-muted"><span>Tạm tính theo giá web</span><span>{{ $money($subtotal) }}</span></div>
        <div>
            <label class="a-label" for="amount">Số tiền thực nhận từ Shopee (đ)</label>
            <input id="amount" type="number" min="0" step="1000" wire:model="amount" class="a-input">
            @error('amount')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <p class="text-xs text-a-muted">Đơn được ghi là đã hoàn thành và đã thanh toán, trừ kho ngay, không tính vào tiền mặt của ca.</p>
        <button class="a-btn a-btn-primary w-full" wire:loading.attr="disabled">Lưu đơn Shopee</button>
    </aside>
</form>
