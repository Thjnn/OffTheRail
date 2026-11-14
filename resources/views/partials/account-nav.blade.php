<nav class="mb-8 flex flex-wrap items-center gap-2 text-sm" aria-label="Tài khoản">
    @foreach ([['account', '/tai-khoan', 'Đơn hàng'], ['account.addresses', '/tai-khoan/dia-chi', 'Sổ địa chỉ'], ['account.wishlist', '/tai-khoan/yeu-thich', 'Yêu thích']] as [$route, $path, $label])
        <a href="{{ url($path) }}" @if (request()->routeIs($route)) aria-current="page" @endif
            class="rounded-full border px-4 py-2 transition {{ request()->routeIs($route) ? 'border-ink bg-ink text-bg' : 'border-line bg-surface hover:border-accent' }}">{{ $label }}</a>
    @endforeach
    <form method="post" action="{{ url('/tai-khoan/dang-xuat') }}" class="ml-auto">
        @csrf
        <button class="text-muted underline underline-offset-4 hover:text-sale">Đăng xuất</button>
    </form>
</nav>
