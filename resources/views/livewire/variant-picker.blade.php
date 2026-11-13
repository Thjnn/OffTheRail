@use('App\Livewire\VariantPicker')
@use('App\Support\Money')

@php
    $price = $variant?->price ?? $product->price;
    $color = $product->colors->firstWhere('id', $colorId);
    $canBuy = $variant !== null && $variant->stock > 0;
    $zalo = shop('zalo_url');
@endphp

<div>
    <p class="text-xl">
        <span class="font-semibold {{ $product->isOnSale() ? 'text-sale' : '' }}">{{ Money::vnd($price) }}</span>
        @if ($product->isOnSale())
            <s class="ml-2 text-sm text-muted">{{ Money::vnd($product->compare_at_price) }}</s>
        @endif
    </p>

    @if ($product->colors->isNotEmpty())
        <p class="mt-6 mb-2.5 text-xs">Màu: <span class="font-semibold">{{ $color?->name }}</span></p>
        <div class="flex flex-wrap gap-2.5" role="radiogroup" aria-label="Chọn màu">
            @foreach ($product->colors as $option)
                <button type="button" wire:click="$set('colorId', {{ $option->id }})" wire:key="color-{{ $option->id }}"
                    role="radio" aria-checked="{{ $option->id === $colorId ? 'true' : 'false' }}"
                    title="{{ $option->name }}" aria-label="{{ $option->name }}"
                    class="size-8 rounded-full border-2 border-surface ring-1 transition {{ $option->id === $colorId ? 'ring-2 ring-accent' : 'ring-line hover:ring-muted' }}"
                    style="background: {{ $option->hex }}"></button>
            @endforeach
        </div>
    @endif

    <div class="mt-6 mb-2.5 flex items-center justify-between text-xs">
        <p>
            Size:
            @if ($soldOut)
                <span class="font-semibold text-sale">Hết hàng</span>
            @elseif ($variant)
                <span class="font-semibold">{{ $variant->size }}</span>
                @if ($variant->stock > 0 && $variant->stock <= VariantPicker::LOW_STOCK)
                    <span class="text-sale">· chỉ còn {{ $variant->stock }}</span>
                @endif
            @else
                <span class="text-muted">chọn size</span>
            @endif
        </p>
        @if ($hasSizeChart)
            <button type="button" class="underline underline-offset-4 hover:text-accent" @click="$dispatch('open-size-chart')">Bảng size</button>
        @endif
    </div>

    @if ($variants->isNotEmpty())
        <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Chọn size">
            @foreach ($variants as $option)
                <button type="button" wire:click="$set('size', @js($option->size))" wire:key="size-{{ $option->id }}"
                    role="radio" aria-checked="{{ $option->size === $size ? 'true' : 'false' }}"
                    data-size="{{ $option->size }}" @disabled($option->stock < 1)
                    class="min-w-12 flex-1 rounded-chip border px-3 py-2.5 text-sm transition
                        {{ $option->stock < 1 ? 'border-line bg-surface-2 text-muted line-through' : ($option->size === $size ? 'border-ink bg-ink text-bg' : 'border-line bg-surface hover:border-accent') }}">{{ $option->size }}</button>
            @endforeach
        </div>
    @endif

    {{-- Thanh mua: dính đáy màn hình trên điện thoại, nằm trong dòng trên máy tính --}}
    <div class="fixed inset-x-0 bottom-0 z-30 flex items-center gap-2 border-t border-line bg-bg/95 px-3 py-2.5 backdrop-blur lg:static lg:mt-7 lg:border-0 lg:bg-transparent lg:p-0 lg:backdrop-blur-none">
        @if (filled($zalo))
            <a href="{{ $zalo }}" target="_blank" rel="noopener" aria-label="Nhắn Zalo hỏi về sản phẩm này"
                title="Link sản phẩm được copy sẵn, dán vào Zalo để hỏi shop"
                @click="navigator.clipboard?.writeText(location.href)"
                class="grid size-11 shrink-0 place-items-center rounded-full bg-[#0068FF] text-[11px] font-bold text-white">Zalo</a>
        @endif
        <button type="button" wire:click="add" @disabled(! $canBuy) class="btn btn-outline flex-1 !py-3">Thêm vào giỏ</button>
        <button type="button" wire:click="add(true)" @disabled(! $canBuy) class="btn btn-primary flex-1 !py-3">
            {{ $soldOut ? 'Hết hàng' : 'Mua ngay' }}
        </button>
    </div>
</div>
