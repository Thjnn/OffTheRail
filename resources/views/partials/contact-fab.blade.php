{{-- Nút liên hệ nổi. Kênh nào chưa cấu hình thì không hiện. --}}
@php
    $zalo = shop('zalo_url');
    $messenger = shop('messenger_url');
    $hotline = shop('hotline');
@endphp

@if (filled($zalo) || filled($messenger) || filled($hotline))
    <div class="fixed right-4 z-30 flex flex-col gap-2 @yield('fab_position', 'bottom-4')" aria-label="Liên hệ nhanh">
        @if (filled($hotline))
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $hotline) }}" aria-label="Gọi hotline {{ $hotline }}"
                class="grid size-11 place-items-center rounded-full bg-ok text-white shadow-lift transition hover:scale-105">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/></svg>
            </a>
        @endif
        @if (filled($messenger))
            <a href="{{ $messenger }}" target="_blank" rel="noopener" aria-label="Nhắn Messenger"
                class="grid size-11 place-items-center rounded-full bg-[#0084FF] text-white shadow-lift transition hover:scale-105">
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.4 2 2 6.1 2 11.6c0 2.9 1.2 5.4 3.2 7.1V22l3-1.7c1.2.3 2.4.5 3.8.5 5.6 0 10-4.1 10-9.6S17.6 2 12 2Zm1 12.9-2.6-2.7-5 2.7 5.5-5.8 2.6 2.7 5-2.7-5.5 5.8Z"/></svg>
            </a>
        @endif
        @if (filled($zalo))
            <a href="{{ $zalo }}" target="_blank" rel="noopener" aria-label="Nhắn Zalo"
                class="grid size-11 place-items-center rounded-full bg-[#0068FF] text-[11px] font-bold text-white shadow-lift transition hover:scale-105">
                Zalo
            </a>
        @endif
    </div>
@endif
