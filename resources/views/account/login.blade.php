@extends('account.auth-card')

@section('title', 'Đăng nhập')
@section('heading')Chào <em>bạn quay lại</em>@endsection

@section('form')
    <form method="post" action="{{ url('/tai-khoan/dang-nhap') }}" class="space-y-4" novalidate>
        @csrf
        <x-field name="phone" label="Số điện thoại" type="tel" autocomplete="tel" required />
        <x-field name="password" label="Mật khẩu" type="password" autocomplete="current-password" required />
        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2"><input type="checkbox" name="remember" value="1" class="size-4 accent-[var(--color-accent)]"> Ghi nhớ</label>
            <a href="{{ url('/tai-khoan/quen-mat-khau') }}" class="text-muted underline underline-offset-4 hover:text-accent">Quên mật khẩu?</a>
        </div>
        <button class="btn btn-primary w-full !py-3">Đăng nhập</button>
    </form>
@endsection

@section('footer')
    Chưa có tài khoản? <a href="{{ url('/tai-khoan/dang-ky') }}" class="font-semibold text-ink underline underline-offset-4 hover:text-accent">Đăng ký</a>
    <p class="mt-2">Không cần tài khoản vẫn đặt hàng được.</p>
@endsection
