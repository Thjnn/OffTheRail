<header class="sticky top-0 z-40 border-b border-line bg-bg/85 backdrop-blur-lg" x-data="{ search: false }">
    <div class="mx-auto grid h-16 max-w-7xl grid-cols-[1fr_auto_1fr] items-center gap-3 px-4 lg:h-[72px] lg:px-8">
        <div class="flex items-center gap-6">
            <button type="button" class="icon-btn lg:hidden" @click="mobileNav = true" aria-label="Mở menu">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <nav class="hidden items-center gap-6 text-[12.5px] font-semibold tracking-[0.08em] uppercase lg:flex" aria-label="Điều hướng chính">
                <a href="{{ url('/san-pham') }}" class="opacity-80 transition hover:opacity-100">Sản phẩm</a>
                @foreach ($navCategories as $category)
                    <a href="{{ url('/danh-muc/'.$category->slug) }}" class="opacity-80 transition hover:opacity-100">{{ $category->name }}</a>
                @endforeach
                <a href="{{ url('/gioi-thieu') }}" class="opacity-80 transition hover:opacity-100">Giới thiệu</a>
                <a href="{{ url('/lien-he') }}" class="opacity-80 transition hover:opacity-100">Liên hệ</a>
            </nav>
        </div>

        @include('partials.brand')

        <div class="flex items-center justify-end gap-2">
            <button type="button" class="icon-btn" @click="search = !search; $nextTick(() => $refs.q?.focus())" aria-label="Tìm kiếm" :aria-expanded="search">
                <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </button>
            <button type="button" class="icon-btn hidden sm:grid" aria-label="Đổi giao diện sáng / tối"
                @click="const dark = document.documentElement.classList.toggle('dark'); try { localStorage.setItem('otr_theme', JSON.stringify(dark ? 'dark' : 'light')) } catch (e) {}">
                <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
            </button>
            <a href="{{ url('/tai-khoan') }}" class="icon-btn hidden sm:grid" aria-label="{{ auth('web')->check() ? 'Tài khoản của tôi' : 'Đăng nhập' }}">
                <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-3.5 4.5-5 8-5s6.5 1.5 8 5"/></svg>
            </a>
            {{-- Giỏ hàng: mở ngăn kéo nếu trang có, không thì đi tới /gio-hang --}}
            <a href="{{ url('/gio-hang') }}" class="icon-btn relative" aria-label="Giỏ hàng" data-cart-icon
                x-data="{ count: {{ app(\App\Support\Cart::class)->count() }} }" @cart-updated.window="count = $event.detail.count"
                @click="if (document.getElementById('cart-drawer')) { $event.preventDefault(); $dispatch('open-cart') }">
                <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 8h14l-1 12H6L5 8Z"/><path d="M9 9V6.5a3 3 0 0 1 6 0V9"/></svg>
                <span x-show="count > 0" x-cloak x-text="count" class="absolute -top-1 -right-1 grid h-[18px] min-w-[18px] place-items-center rounded-full bg-accent px-1 text-[10px] font-bold text-white dark:text-bg"></span>
            </a>
        </div>
    </div>

    <form x-show="search" x-cloak x-transition.opacity action="{{ url('/san-pham') }}" method="get" role="search"
        class="border-t border-line px-4 py-3 lg:px-8" @keydown.escape="search = false">
        <div class="mx-auto flex max-w-2xl gap-2">
            <input x-ref="q" type="search" name="q" placeholder="Tìm áo thun, sơ mi…" aria-label="Tìm sản phẩm"
                class="w-full rounded-full border border-line bg-surface px-4 py-2 text-sm outline-none focus:border-accent">
            <button class="btn btn-primary shrink-0">Tìm</button>
        </div>
    </form>
</header>
