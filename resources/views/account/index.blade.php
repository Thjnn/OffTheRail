@extends('layouts.shop')
@use('App\Support\Money')

@section('title', 'Tài khoản')

@section('content')
    <div class="mx-auto max-w-3xl px-4 pt-10 lg:pt-16">
        <p class="eyebrow mb-2">Tài khoản</p>
        <h1 class="display mb-6 text-4xl">Chào <em>{{ auth('web')->user()->name }}</em></h1>
        @include('partials.account-nav')

        @if ($orders->isEmpty())
            <div class="rounded-card bg-surface p-8 text-center shadow-soft">
                <p class="text-muted">Bạn chưa có đơn hàng nào.</p>
                <a href="{{ url('/san-pham') }}" class="btn btn-primary mt-5">Xem sản phẩm</a>
            </div>
        @else
            <ul class="divide-y divide-line overflow-hidden rounded-card bg-surface shadow-soft">
                @foreach ($orders as $order)
                    <li>
                        <a href="{{ url('/tai-khoan/don-hang/'.$order->code) }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-surface-2">
                            <div>
                                <p class="font-mono font-semibold tracking-wider">{{ $order->code }}</p>
                                <p class="text-xs text-muted">{{ $order->created_at->format('d/m/Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold tabular-nums">{{ Money::vnd($order->total) }}</p>
                                <p class="text-xs text-muted">{{ $order->status->label() }}</p>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-6">{{ $orders->links() }}</div>
        @endif

        <p class="mt-6 text-xs text-muted">Đơn đặt trước khi tạo tài khoản: xem bằng <a href="{{ url('/tra-cuu-don') }}" class="underline underline-offset-4 hover:text-accent">tra cứu đơn</a>.</p>
    </div>
@endsection
