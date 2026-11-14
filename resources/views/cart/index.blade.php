@extends('layouts.shop')

@section('title', 'Giỏ hàng')

@section('content')
    <div class="mx-auto max-w-2xl px-4 pt-10 lg:pt-16">
        <p class="eyebrow mb-2">Giỏ hàng</p>
        <h1 class="display mb-6 text-4xl sm:text-5xl">Món bạn <em>đã chọn</em></h1>
        <livewire:cart-panel />
    </div>
@endsection
