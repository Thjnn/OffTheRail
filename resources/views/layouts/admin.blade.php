<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@hasSection('title')@yield('title') · @endif Quản lý {{ shop('name') }}</title>
    @fonts
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    <style>:root { --color-accent: {{ shop('accent') }}; }</style>
    @livewireStyles
</head>
<body class="min-h-screen">
    <div class="flex min-h-screen">
        @include('admin.partials.sidebar')

        <main class="min-w-0 flex-1 px-6 py-5 lg:px-8">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-lg font-semibold">@yield('title')</h1>
                <div class="flex flex-wrap items-center gap-2">@yield('actions')</div>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-md border border-a-up/30 bg-a-up/5 px-3 py-2 text-a-up" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-md border border-a-down/30 bg-a-down/5 px-3 py-2 text-a-down" role="alert">
                    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @livewireScripts
</body>
</html>
