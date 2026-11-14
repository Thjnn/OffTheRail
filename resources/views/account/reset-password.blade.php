@extends('account.auth-card')

@section('title', 'Đặt lại mật khẩu')
@section('heading')Đặt lại <em>mật khẩu</em>@endsection

@section('form')
    <form method="post" action="{{ url('/tai-khoan/dat-lai-mat-khau') }}" class="space-y-4" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field name="email" label="Email" type="email" :value="$email" autocomplete="email" required />
        <x-field name="password" label="Mật khẩu mới (tối thiểu 8 ký tự)" type="password" autocomplete="new-password" required />
        <x-field name="password_confirmation" label="Nhập lại mật khẩu mới" type="password" autocomplete="new-password" required />
        <button class="btn btn-primary w-full !py-3">Đặt lại mật khẩu</button>
    </form>
@endsection
