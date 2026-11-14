@extends('layouts.shop')

@section('title', 'Sổ địa chỉ')

@section('content')
    <div class="mx-auto max-w-3xl px-4 pt-10 lg:pt-16">
        <p class="eyebrow mb-2">Tài khoản</p>
        <h1 class="display mb-6 text-4xl">Sổ <em>địa chỉ</em></h1>
        @include('partials.account-nav')
        <livewire:address-book />
    </div>
@endsection
