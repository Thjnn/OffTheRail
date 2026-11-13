@extends('layouts.shop')

@section('content')
    {{-- Banner --}}
    <section class="mx-auto max-w-7xl px-4 pt-4 lg:px-8 lg:pt-6">
        @if ($banners->isNotEmpty())
            <div class="swiper js-hero-swiper overflow-hidden rounded-card" data-hero>
                <div class="swiper-wrapper">
                    @foreach ($banners as $banner)
                        <div class="swiper-slide relative">
                            <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}"
                                @if (! $loop->first) loading="lazy" @endif
                                class="h-[62vh] max-h-[640px] min-h-[380px] w-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
                            <div class="absolute inset-x-0 bottom-0 p-6 pb-12 text-white sm:p-10 sm:pb-14">
                                @if ($banner->subtitle)
                                    <p class="eyebrow mb-2 text-white/85">{{ $banner->subtitle }}</p>
                                @endif
                                @if ($banner->title)
                                    <h2 class="display max-w-xl text-4xl italic sm:text-6xl">{{ $banner->title }}</h2>
                                @endif
                                @if ($banner->link)
                                    <a href="{{ url($banner->link) }}" class="btn mt-5 bg-white text-[#1b1b1f] hover:bg-white/90">
                                        {{ $banner->link_label ?: 'Xem ngay' }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="swiper-pagination"></div>
            </div>
        @else
            <div class="rounded-card bg-surface-2 px-6 py-16 text-center sm:py-24" data-reveal>
                <p class="eyebrow mb-3">{{ shop('name') }}</p>
                <h1 class="display mx-auto max-w-2xl text-4xl sm:text-6xl"><em>{{ shop('tagline') }}</em></h1>
                <a href="{{ url('/san-pham') }}" class="btn btn-primary mt-7">Xem sản phẩm</a>
            </div>
        @endif
    </section>

    @foreach ($sections as ['section' => $section, 'items' => $items])
        <section class="mx-auto max-w-7xl px-4 pt-14 lg:px-8 lg:pt-20" data-reveal>
            @switch($section->type)
                @case('new_arrivals')
                    <div class="mb-5 flex items-end justify-between gap-4">
                        <div>
                            <p class="eyebrow mb-1.5">Vừa ra mắt</p>
                            <h2 class="display text-3xl sm:text-4xl">{{ $section->title }}</h2>
                        </div>
                        <a href="{{ url('/san-pham') }}" class="shrink-0 text-sm font-semibold underline underline-offset-4 hover:text-accent">Xem tất cả</a>
                    </div>
                    {{-- Vuốt ngang bằng CSS scroll-snap, không cần thư viện --}}
                    <div class="no-scrollbar -mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-2 lg:mx-0 lg:grid lg:grid-cols-4 lg:gap-6 lg:overflow-visible lg:px-0">
                        @foreach ($items as $product)
                            <div class="w-[46%] shrink-0 snap-start sm:w-[30%] lg:w-auto">
                                @include('partials.product-card', ['product' => $product])
                            </div>
                        @endforeach
                    </div>
                    @break

                @case('categories')
                    <p class="eyebrow mb-1.5">Danh mục</p>
                    <h2 class="display mb-5 text-3xl sm:text-4xl">{{ $section->title }}</h2>
                    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 lg:gap-6">
                        @foreach ($items as $category)
                            @php $cover = $category->products->first()?->coverImageUrl(); @endphp
                            <a href="{{ url('/danh-muc/'.$category->slug) }}" class="group relative block overflow-hidden rounded-card bg-surface-2">
                                @if ($cover)
                                    <img src="{{ $cover }}" alt="" loading="lazy" class="aspect-[4/5] w-full object-cover transition duration-500 group-hover:scale-105 sm:aspect-[4/3]">
                                @else
                                    <div class="aspect-[4/5] w-full sm:aspect-[4/3]"></div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-black/55 to-transparent"></div>
                                <span class="absolute bottom-4 left-4 font-serif text-xl text-white sm:text-2xl">{{ $category->name }}</span>
                            </a>
                        @endforeach
                    </div>
                    @break

                @case('featured')
                    <p class="eyebrow mb-1.5">Được yêu thích</p>
                    <h2 class="display mb-5 text-3xl sm:text-4xl">{{ $section->title }}</h2>
                    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-6" data-stagger>
                        @foreach ($items as $product)
                            @include('partials.product-card', ['product' => $product])
                        @endforeach
                    </div>
                    @break

                @case('story')
                    <div class="rounded-card bg-surface-2 px-6 py-12 sm:px-14 sm:py-16">
                        <p class="eyebrow mb-3">{{ $section->option('eyebrow', $section->title) }}</p>
                        <h2 class="display max-w-3xl text-2xl sm:text-4xl">{{ $section->title }}</h2>
                        @if ($section->option('body'))
                            <p class="mt-4 max-w-2xl leading-relaxed text-muted">{{ $section->option('body') }}</p>
                        @endif
                        @if ($section->option('link'))
                            <a href="{{ url($section->option('link')) }}" class="btn btn-outline mt-6">{{ $section->option('link_label', 'Đọc thêm') }}</a>
                        @endif
                    </div>
                    @break
            @endswitch
        </section>
    @endforeach
@endsection
