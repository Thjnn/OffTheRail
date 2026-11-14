<div x-show="mobileNav" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menu" @keydown.escape.window="mobileNav = false">
    <div class="absolute inset-0 bg-black/45 backdrop-blur-[2px]" x-show="mobileNav" x-transition.opacity @click="mobileNav = false"></div>

    <div class="absolute inset-y-0 left-0 flex w-[82%] max-w-[340px] flex-col gap-1 border-r border-line bg-surface p-6 shadow-lift"
        x-show="mobileNav"
        x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="-translate-x-full"
        x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="-translate-x-full">
        <div class="mb-4 flex items-center justify-between">
            @include('partials.brand')
            <button type="button" class="icon-btn text-xl" @click="mobileNav = false" aria-label="Đóng menu">×</button>
        </div>

        <nav class="flex flex-col text-base font-semibold" aria-label="Menu di động">
            <a class="rounded-chip px-3 py-3.5 hover:bg-surface-2" href="{{ url('/san-pham') }}">Tất cả sản phẩm</a>
            @foreach ($navCategories as $category)
                <a class="rounded-chip px-3 py-3.5 hover:bg-surface-2" href="{{ url('/danh-muc/'.$category->slug) }}">{{ $category->name }}</a>
            @endforeach
            <div class="my-3 h-px bg-line"></div>
            <a class="rounded-chip px-3 py-3.5 hover:bg-surface-2" href="{{ url('/gio-hang') }}">Giỏ hàng</a>
            <a class="rounded-chip px-3 py-3.5 hover:bg-surface-2" href="{{ url('/tai-khoan') }}">{{ auth('web')->check() ? 'Tài khoản của tôi' : 'Đăng nhập / Đăng ký' }}</a>
            <a class="rounded-chip px-3 py-3.5 hover:bg-surface-2" href="{{ url('/tra-cuu-don') }}">Tra cứu đơn</a>
            <a class="rounded-chip px-3 py-3.5 hover:bg-surface-2" href="{{ url('/gioi-thieu') }}">Giới thiệu</a>
            <a class="rounded-chip px-3 py-3.5 hover:bg-surface-2" href="{{ url('/lien-he') }}">Liên hệ</a>
        </nav>

        <button type="button" class="mt-auto flex items-center gap-2 rounded-chip px-3 py-3 text-sm text-muted hover:bg-surface-2"
            @click="const dark = document.documentElement.classList.toggle('dark'); try { localStorage.setItem('otr_theme', JSON.stringify(dark ? 'dark' : 'light')) } catch (e) {}">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
            Đổi giao diện sáng / tối
        </button>
    </div>
</div>
