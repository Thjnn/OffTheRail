@extends('layouts.shop')

@section('title', $category?->name ?? 'Sản phẩm')

@section('content')
    <div class="mx-auto max-w-7xl px-4 pt-8 lg:px-8 lg:pt-12">
        <header class="mb-8" data-reveal>
            <p class="eyebrow mb-2">{{ $category ? 'Danh mục' : 'Cửa hàng' }}</p>
            <h1 class="display text-4xl sm:text-5xl">{{ $category?->name ?? 'Tất cả sản phẩm' }}</h1>
        </header>

        <livewire:product-list :category="$category?->slug" />
    </div>
@endsection
