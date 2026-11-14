{{-- Khung chung cho các trang đăng nhập/đăng ký/quên mật khẩu --}}
@extends('layouts.shop')

@section('meta')
    <meta name="robots" content="noindex">
@endsection

@section('content')
    <div class="mx-auto max-w-md px-4 pt-10 lg:pt-16">
        <p class="eyebrow mb-2">Tài khoản</p>
        <h1 class="display mb-6 text-4xl">@yield('heading')</h1>

        <div class="rounded-card bg-surface p-6 shadow-soft">
            @if (session('status'))
                <p class="mb-4 rounded-chip bg-ok/10 px-4 py-3 text-sm text-ok" role="status">{{ session('status') }}</p>
            @endif
            @yield('form')
        </div>

        <div class="mt-5 text-center text-sm text-muted">@yield('footer')</div>
    </div>
@endsection
