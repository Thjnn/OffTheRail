@extends('layouts.shop')

@section('title', 'Thanh toán')

@section('content')
    <div class="mx-auto max-w-6xl px-4 pt-10 lg:px-8 lg:pt-14">
        <p class="eyebrow mb-2">Thanh toán</p>
        <h1 class="display mb-8 text-4xl sm:text-5xl">Gần xong <em>rồi</em></h1>
        <livewire:checkout />
    </div>
@endsection
