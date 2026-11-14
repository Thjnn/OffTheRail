@extends('account.auth-card')

@section('title', 'Đăng ký')
@section('heading')Tạo <em>tài khoản</em>@endsection

@section('form')
    <form method="post" action="{{ url('/tai-khoan/dang-ky') }}" class="space-y-4" novalidate>
        @csrf
        <x-field name="name" label="Họ tên" autocomplete="name" required />
        <x-field name="phone" label="Số điện thoại" type="tel" autocomplete="tel" required />
        <x-field name="email" label="Email (để lấy lại mật khẩu khi quên)" type="email" autocomplete="email" />
        <x-field name="password" label="Mật khẩu (tối thiểu 8 ký tự)" type="password" autocomplete="new-password" required />
        <x-field name="password_confirmation" label="Nhập lại mật khẩu" type="password" autocomplete="new-password" required />
        <button class="btn btn-primary w-full !py-3">Đăng ký</button>
    </form>
@endsection

@section('footer')
    Đã có tài khoản? <a href="{{ url('/tai-khoan/dang-nhap') }}" class="font-semibold text-ink underline underline-offset-4 hover:text-accent">Đăng nhập</a>
@endsection
