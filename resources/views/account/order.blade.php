@extends('layouts.shop')

@section('title', 'Đơn '.$order->code)

@section('content')
    <div class="mx-auto max-w-3xl px-4 pt-10 lg:pt-16">
        <a href="{{ url('/tai-khoan') }}" class="text-sm text-muted underline underline-offset-4 hover:text-accent">← Đơn hàng của tôi</a>
        <h1 class="display mt-3 mb-6 text-4xl">Chi tiết <em>đơn hàng</em></h1>
        @include('partials.order-detail', ['order' => $order])
    </div>
@endsection
