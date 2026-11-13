{{-- Thẻ sản phẩm. Cần $product đã nạp sẵn images và variants. --}}
@php
    $cover = $product->coverImageUrl();
    $soldOut = $product->isSoldOut();
@endphp

<a href="{{ url('/san-pham/'.$product->slug) }}" class="group block" data-card>
    <div class="relative overflow-hidden rounded-card bg-surface p-1.5 shadow-soft transition duration-300 group-hover:shadow-lift">
        @if ($cover)
            <img src="{{ $cover }}" alt="{{ $product->name }}" loading="lazy"
                class="aspect-[4/5] w-full rounded-[12px] object-cover transition duration-500 group-hover:scale-[1.03] {{ $soldOut ? 'opacity-60' : '' }}">
        @else
            <div class="grid aspect-[4/5] w-full place-items-center rounded-[12px] bg-surface-2 font-serif text-3xl text-muted italic" aria-hidden="true">
                {{ mb_substr($product->name, 0, 1) }}
            </div>
        @endif

        @if ($soldOut)
            <span class="absolute top-3 left-3 rounded-full bg-ink px-2.5 py-1 text-[10px] font-semibold tracking-wide text-bg uppercase">Hết hàng</span>
        @elseif ($product->isOnSale())
            <span class="absolute top-3 left-3 rounded-full bg-sale px-2.5 py-1 text-[10px] font-semibold text-white">-{{ $product->discountPercent() }}%</span>
        @elseif ($product->isNew())
            <span class="absolute top-3 left-3 rounded-full bg-accent px-2.5 py-1 text-[10px] font-semibold tracking-wide text-white uppercase dark:text-bg">Mới</span>
        @endif
    </div>

    <div class="px-1 pt-3">
        <h3 class="font-serif text-[15px] leading-snug">{{ $product->name }}</h3>
        <p class="mt-0.5 text-sm">
            <span class="font-semibold {{ $product->isOnSale() ? 'text-sale' : '' }}">{{ \App\Support\Money::vnd($product->price) }}</span>
            @if ($product->isOnSale())
                <s class="ml-1.5 text-xs text-muted">{{ \App\Support\Money::vnd($product->compare_at_price) }}</s>
            @endif
        </p>
    </div>
</a>
