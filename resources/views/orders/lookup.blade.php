@extends('layouts.shop')

@section('title', 'Tra cứu đơn')

@section('meta')
    <meta name="robots" content="noindex">
@endsection

@section('content')
    <div class="mx-auto max-w-2xl px-4 pt-10 lg:pt-16">
        <p class="eyebrow mb-2">Tra cứu đơn</p>
        <h1 class="display mb-6 text-4xl sm:text-5xl">Đơn của bạn <em>đến đâu rồi?</em></h1>

        <form method="post" action="{{ url('/tra-cuu-don') }}" class="grid gap-4 rounded-card bg-surface p-5 shadow-soft sm:grid-cols-[1fr_1fr_auto] sm:items-end sm:p-6" novalidate>
            @csrf
            <div>
                <label for="code" class="mb-1.5 block text-sm font-medium">Mã đơn</label>
                <input id="code" name="code" value="{{ old('code', $order?->code) }}" placeholder="DH…" autocapitalize="characters" required
                    class="w-full rounded-chip border border-line bg-surface px-4 py-2.5 font-mono uppercase outline-none focus:border-accent">
            </div>
            <div>
                <label for="phone" class="mb-1.5 block text-sm font-medium">Số điện thoại đặt hàng</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" required
                    class="w-full rounded-chip border border-line bg-surface px-4 py-2.5 outline-none focus:border-accent">
            </div>
            <button class="btn btn-primary !py-3">Tra cứu</button>
            @error('code') <p class="text-sm text-sale sm:col-span-3" role="alert">{{ $message }}</p> @enderror
        </form>

        @if ($order)
            <div class="mt-8" data-reveal>
                @include('partials.order-detail', ['order' => $order])
            </div>
        @endif
    </div>
@endsection
