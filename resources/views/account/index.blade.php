@extends('layouts.shop')

@section('title', 'Tài khoản')

@section('content')
    <div class="mx-auto max-w-3xl px-4 pt-10 lg:pt-16">
        <p class="eyebrow mb-2">Tài khoản</p>
        <h1 class="display text-4xl">Chào <em>{{ auth('web')->user()->name }}</em></h1>
        <form method="post" action="{{ url('/tai-khoan/dang-xuat') }}" class="mt-6">
            @csrf
            <button class="btn btn-outline">Đăng xuất</button>
        </form>
    </div>
@endsection
