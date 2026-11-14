<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') · @endif{{ shop('name') }}</title>
    @hasSection('meta')
        @yield('meta')
    @else
        <meta name="description" content="{{ shop('tagline') }}">
    @endif

    {{-- Áp chế độ tối trước khi vẽ trang để không bị nháy --}}
    <script>
        try { if (JSON.parse(localStorage.getItem('otr_theme')) === 'dark') document.documentElement.classList.add('dark'); } catch (e) {}
    </script>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root { --color-accent: {{ shop('accent') }}; }</style>
    @livewireStyles
</head>
<body class="min-h-screen" x-data="{ mobileNav: false }">
    @include('partials.announcement')
    @include('partials.header')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.mobile-nav')
    @include('partials.contact-fab')

    {{-- Ngăn kéo giỏ hàng: trang giỏ và trang thanh toán đã có giỏ ngay trong nội dung --}}
    @unless (request()->routeIs('cart', 'checkout'))
        <livewire:cart-panel :drawer="true" />
    @endunless

    @livewireScripts
</body>
</html>
