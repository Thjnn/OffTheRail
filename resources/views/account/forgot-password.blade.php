@extends('account.auth-card')

@section('title', 'Quên mật khẩu')
@section('heading')Quên <em>mật khẩu</em>@endsection

@section('form')
    <form method="post" action="{{ url('/tai-khoan/quen-mat-khau') }}" class="space-y-4" novalidate>
        @csrf
        <x-field name="email" label="Email đã đăng ký" type="email" autocomplete="email" required />
        <button class="btn btn-primary w-full !py-3">Gửi link đặt lại</button>
    </form>
@endsection

@section('footer')
    Tài khoản không có email? Nhắn shop qua Zalo/hotline để được hỗ trợ.
    <p class="mt-2"><a href="{{ url('/tai-khoan/dang-nhap') }}" class="underline underline-offset-4 hover:text-accent">Quay lại đăng nhập</a></p>
@endsection
