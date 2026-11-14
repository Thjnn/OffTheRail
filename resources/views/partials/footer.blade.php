<footer class="mt-20 border-t border-line bg-surface-2">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
        <div class="lg:col-span-2">
            @include('partials.brand')
            <p class="mt-3 max-w-sm text-sm text-muted">{{ shop('tagline') }}</p>
        </div>

        <div>
            <p class="eyebrow mb-3">Mua sắm</p>
            <ul class="space-y-2 text-sm">
                <li><a class="hover:text-accent" href="{{ url('/san-pham') }}">Tất cả sản phẩm</a></li>
                @foreach ($navCategories as $category)
                    <li><a class="hover:text-accent" href="{{ url('/danh-muc/'.$category->slug) }}">{{ $category->name }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <p class="eyebrow mb-3">Hỗ trợ</p>
            <ul class="space-y-2 text-sm">
                <li><a class="hover:text-accent" href="{{ url('/gioi-thieu') }}">Về chúng tôi</a></li>
                <li><a class="hover:text-accent" href="{{ url('/lien-he') }}">Liên hệ</a></li>
                <li><a class="hover:text-accent" href="{{ url('/tra-cuu-don') }}">Tra cứu đơn</a></li>
                @foreach ((array) shop('policy_lines', []) as $line)
                    <li class="text-muted">{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="border-t border-line px-4 py-5 text-center text-xs text-muted">
        © {{ now()->year }} {{ shop('name') }}
    </div>
</footer>
