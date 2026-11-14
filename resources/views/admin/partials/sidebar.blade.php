@php
    $user = auth('admin')->user();
    $items = [
        ['/admin', 'dashboard', 'Tổng quan', false],
        ['/admin/don-hang', 'orders', 'Đơn hàng', false],
        ['/admin/san-pham', 'products', 'Sản phẩm', false],
        ['/admin/kho', 'stock', 'Kho hàng', false],
        ['/admin/khach-hang', 'customers', 'Khách hàng', true],
        ['/admin/nhan-vien', 'staff', 'Nhân viên', true],
        ['/admin/noi-dung', 'content', 'Nội dung web', true],
        ['/admin/cai-dat', 'settings', 'Cài đặt', true],
        ['/admin/nhat-ky', 'log', 'Nhật ký', true],
    ];
@endphp
<aside class="sticky top-0 flex h-screen w-56 shrink-0 flex-col border-r border-a-line bg-a-surface px-3 py-4">
    <a href="{{ url('/admin') }}" class="mb-6 flex items-center gap-2 px-2 font-semibold">
        <span class="size-5 rounded bg-accent"></span>
        <span class="truncate">{{ shop('name') }}</span>
    </a>

    <nav class="flex flex-1 flex-col gap-0.5" aria-label="Menu quản lý">
        @foreach ($items as [$href, $icon, $label, $ownerOnly])
            @continue($ownerOnly && ! $user->isOwner())
            @php
                $path = ltrim($href, '/');
                $active = $href === '/admin' ? request()->is('admin') : request()->is($path, $path.'/*');
            @endphp
            <a href="{{ url($href) }}" @if ($active) aria-current="page" @endif
                class="flex items-center gap-2.5 rounded-md px-2 py-1.5 {{ $active ? 'bg-accent/10 font-semibold text-accent' : 'text-a-ink/80 hover:bg-a-soft' }}">
                <x-admin.icon :name="$icon" />
                <span>{{ $label }}</span>
                @if ($href === '/admin/don-hang')
                    <livewire:admin.pending-badge />
                @endif
            </a>
        @endforeach
    </nav>

    <div class="border-t border-a-line pt-3">
        <p class="truncate px-2 font-medium">{{ $user->name }}</p>
        <p class="px-2 text-xs text-a-muted">{{ $user->roleLabel() }}</p>
        <form method="POST" action="{{ url('/admin/dang-xuat') }}" class="mt-2">
            @csrf
            <button class="flex w-full items-center gap-2.5 rounded-md px-2 py-1.5 text-a-muted hover:bg-a-soft">
                <x-admin.icon name="logout" /> Đăng xuất
            </button>
        </form>
    </div>
</aside>
