<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Đăng nhập quản lý · {{ shop('name') }}</title>
    @fonts
    @vite(['resources/css/admin.css'])
    <style>:root { --color-accent: {{ shop('accent') }}; }</style>
</head>
<body class="grid min-h-screen place-items-center px-4">
    <form method="POST" action="{{ url('/admin/dang-nhap') }}" class="a-card w-full max-w-sm p-6">
        @csrf
        <div class="mb-6 flex items-center gap-2 font-semibold">
            <span class="size-5 rounded bg-accent"></span> {{ shop('name') }}
        </div>
        <h1 class="mb-1 text-lg font-semibold">Đăng nhập</h1>
        <p class="mb-5 text-a-muted">Dành cho chủ shop và nhân viên.</p>

        <label class="a-label" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="a-input">
        @error('email')<p class="a-error">{{ $message }}</p>@enderror

        <label class="a-label mt-3" for="password">Mật khẩu</label>
        <input id="password" name="password" type="password" required autocomplete="current-password" class="a-input">

        <label class="mt-3 flex items-center gap-2 text-a-muted">
            <input type="checkbox" name="remember" value="1"> Ghi nhớ đăng nhập
        </label>

        <button class="a-btn a-btn-primary mt-5 w-full py-2">Đăng nhập</button>
    </form>
</body>
</html>
