@extends('layouts.shop')

@section('title', $product->name)

@section('meta')
    <meta name="description" content="{{ $ogDescription }}">
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $product->name }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($cover)
        <meta property="og:image" content="{{ $cover }}">
    @endif
@endsection

{{-- Nút liên hệ nổi nằm trên thanh mua dính đáy (điện thoại) --}}
@section('fab_position', 'bottom-20 lg:bottom-4')

@section('content')
    @php
        $sizeChart = $product->category?->size_chart;
        $hasSizeChart = is_array($sizeChart) && ! empty($sizeChart['rows']);
        $firstColorId = $product->colors->first()?->id;
    @endphp

    <article class="pb-20 lg:pb-0">
        <div class="mx-auto max-w-7xl lg:grid lg:grid-cols-[1.15fr_1fr] lg:gap-12 lg:px-8 lg:pt-8">
            {{-- Ảnh đầu: tràn màn hình trên điện thoại, đổi theo màu đang chọn --}}
            <div class="overflow-hidden bg-surface lg:rounded-card"
                x-data="{ images: @js($colorImages), src: @js($colorImages[$firstColorId] ?? $cover) }"
                @color-changed.window="src = images[$event.detail.colorId] ?? src">
                @if ($cover)
                    <img :src="src" src="{{ $colorImages[$firstColorId] ?? $cover }}" alt="{{ $product->name }}"
                        class="aspect-[4/5] w-full object-cover" data-hero-image>
                @else
                    <div class="grid aspect-[4/5] w-full place-items-center bg-surface-2 font-serif text-7xl text-muted italic" aria-hidden="true">
                        {{ mb_substr($product->name, 0, 1) }}
                    </div>
                @endif
            </div>

            {{-- Tấm thông tin: bo góc, đè lên mép ảnh trên điện thoại --}}
            <div class="relative -mt-5 rounded-t-[22px] bg-bg px-5 pt-6 lg:mt-0 lg:self-start lg:rounded-none lg:px-0 lg:pt-4 lg:sticky lg:top-24">
                <p class="eyebrow mb-2">
                    <a href="{{ url('/danh-muc/'.$product->category->slug) }}" class="hover:text-accent">{{ $product->category->name }}</a>
                    @if ($product->isNew()) · Mẫu mới @endif
                    @if ($product->isOnSale()) · Giảm {{ $product->discountPercent() }}% @endif
                </p>
                <div class="flex items-start justify-between gap-3">
                    <h1 class="display text-4xl sm:text-5xl">{{ $product->name }}</h1>
                    <livewire:wishlist-button :product-id="$product->id" />
                </div>
                @if (filled($product->quote))
                    <p class="mt-2 font-serif text-[15px] text-muted italic">“{{ $product->quote }}”</p>
                @endif

                <div class="mt-4">
                    <livewire:variant-picker :product="$product" :has-size-chart="$hasSizeChart" />
                </div>

                @if (filled($product->description))
                    <p class="mt-7 leading-relaxed">{{ $product->description }}</p>
                @endif

                @if (! empty(shop('policy_lines')))
                    <ul class="mt-6 divide-y divide-line border-y border-line text-sm">
                        @foreach ((array) shop('policy_lines') as $line)
                            <li class="py-3">{{ $line }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Khối ảnh ↔ chữ xen kẽ. Sản phẩm không có khối nào thì bỏ hẳn phần này. --}}
        @if ($product->blocks->isNotEmpty())
            <div class="mx-auto mt-12 max-w-6xl space-y-10 lg:mt-24 lg:space-y-24 lg:px-8">
                @foreach ($product->blocks as $block)
                    <section class="items-center gap-12 lg:grid lg:grid-cols-2" data-reveal>
                        @if ($block->imageUrl())
                            <img src="{{ $block->imageUrl() }}" alt="" loading="lazy"
                                class="aspect-[4/5] w-full object-cover lg:rounded-card {{ $loop->even ? 'lg:order-2' : '' }}">
                        @endif
                        @if (filled($block->body))
                            <p class="px-5 pt-5 font-serif text-lg leading-relaxed sm:text-xl lg:px-0 lg:pt-0 {{ $block->imageUrl() ? '' : 'lg:col-span-2 lg:mx-auto lg:max-w-2xl lg:text-center' }}">
                                {{ $block->body }}
                            </p>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif

        @if ($related->isNotEmpty())
            <section class="mx-auto max-w-7xl px-4 pt-16 lg:px-8 lg:pt-24" data-reveal>
                <p class="eyebrow mb-1.5">Cùng loại</p>
                <h2 class="display mb-5 text-3xl sm:text-4xl">Có thể bạn thích</h2>
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-6">
                    @foreach ($related as $item)
                        @include('partials.product-card', ['product' => $item])
                    @endforeach
                </div>
            </section>
        @endif
    </article>

    @if ($hasSizeChart)
        @include('partials.size-chart-modal', ['chart' => $sizeChart, 'categoryName' => $product->category->name])
    @endif
@endsection
