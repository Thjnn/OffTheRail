@extends('layouts.shop')

@section('title', 'Yêu thích')

@section('content')
    <div class="mx-auto max-w-5xl px-4 pt-10 lg:px-8 lg:pt-16">
        <p class="eyebrow mb-2">Tài khoản</p>
        <h1 class="display mb-6 text-4xl">Món bạn <em>yêu thích</em></h1>
        @include('partials.account-nav')

        @if ($products->isEmpty())
            <p class="text-sm text-muted">Chưa có món nào. Bấm biểu tượng tim ở trang sản phẩm để lưu lại nhé.</p>
        @else
            <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4" data-stagger>
                @foreach ($products as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif
    </div>
@endsection
