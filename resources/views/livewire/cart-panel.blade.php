@use('App\Support\Money')

<div @if ($drawer) id="cart-drawer" data-cart-drawer x-data="{ open: $wire.entangle('open') }" @keydown.escape.window="open = false" @endif>
    @if ($drawer)
        <div x-show="open" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Giỏ hàng">
            <div class="absolute inset-0 bg-black/45 backdrop-blur-[2px]" x-show="open" x-transition.opacity @click="open = false"></div>
            <div class="absolute inset-y-0 right-0 flex w-[92%] max-w-[420px] flex-col border-l border-line bg-surface shadow-lift"
                x-show="open"
                x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full"
                x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="translate-x-full">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="display text-2xl">Giỏ hàng</h2>
                    <button type="button" class="icon-btn text-xl" @click="open = false" aria-label="Đóng giỏ hàng">×</button>
                </div>
    @endif

    <div class="{{ $drawer ? 'flex min-h-0 flex-1 flex-col' : '' }}">
        @if ($notice)
            <p class="{{ $drawer ? 'mx-5' : '' }} mt-4 rounded-chip bg-sale/10 px-4 py-3 text-sm text-sale" role="alert">{{ $notice }}</p>
        @endif

        @if ($lines->isEmpty())
            <div class="px-5 py-16 text-center">
                <p class="text-muted">Giỏ hàng đang trống.</p>
                <a href="{{ url('/san-pham') }}" class="btn btn-primary mt-5">Xem sản phẩm</a>
            </div>
        @else
            <ul class="{{ $drawer ? 'flex-1 overflow-y-auto px-5' : '' }} divide-y divide-line">
                @foreach ($lines as $line)
                    @php $v = $line->variant; @endphp
                    <li class="flex gap-4 py-4" wire:key="line-{{ $v->id }}">
                        <a href="{{ url('/san-pham/'.$v->product->slug) }}" class="block h-24 w-20 shrink-0 overflow-hidden rounded-chip bg-surface-2">
                            @if ($line->imageUrl())
                                <img src="{{ $line->imageUrl() }}" alt="" class="size-full object-cover" loading="lazy">
                            @endif
                        </a>
                        <div class="flex min-w-0 flex-1 flex-col">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ url('/san-pham/'.$v->product->slug) }}" class="block truncate font-semibold hover:text-accent">{{ $v->product->name }}</a>
                                    <p class="text-xs text-muted">{{ $v->color?->name }} · {{ $v->size }} · {{ Money::vnd($line->unitPrice()) }}</p>
                                </div>
                                <button type="button" wire:click="remove({{ $v->id }})" class="text-xs text-muted underline underline-offset-4 hover:text-sale"
                                    aria-label="Xoá {{ $v->product->name }} khỏi giỏ">Xoá</button>
                            </div>

                            @unless ($line->isAvailable())
                                <p class="mt-1 text-xs font-semibold text-sale">{{ $v->stock > 0 ? 'Chỉ còn '.$v->stock : 'Hết hàng' }}</p>
                            @endunless

                            <div class="mt-auto flex items-center justify-between pt-2">
                                <div class="flex items-center rounded-full border border-line">
                                    <button type="button" class="size-8" wire:click="setQty({{ $v->id }}, {{ $line->qty - 1 }})" aria-label="Giảm số lượng">−</button>
                                    <span class="w-7 text-center text-sm tabular-nums" aria-live="polite">{{ $line->qty }}</span>
                                    <button type="button" class="size-8 disabled:opacity-40" wire:click="setQty({{ $v->id }}, {{ $line->qty + 1 }})"
                                        @disabled($line->qty >= $v->stock) aria-label="Tăng số lượng">+</button>
                                </div>
                                <span class="text-sm font-semibold tabular-nums">{{ Money::vnd($line->total()) }}</span>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="{{ $drawer ? 'border-t border-line px-5 py-4' : 'mt-6 rounded-card bg-surface p-5 shadow-soft' }}">
                @if ($freeShipHint)
                    <p class="mb-3 text-xs text-muted">{{ $freeShipHint }}</p>
                @endif
                <div class="flex items-baseline justify-between">
                    <span class="text-sm text-muted">Tạm tính</span>
                    <span class="text-lg font-semibold tabular-nums">{{ Money::vnd($subtotal) }}</span>
                </div>
                @if ($blocked)
                    <p class="mt-2 text-xs text-sale">Một số món không còn đủ hàng. Bạn giảm số lượng hoặc xoá để tiếp tục nhé.</p>
                    <button type="button" data-checkout disabled class="btn btn-primary mt-4 w-full !py-3">Thanh toán</button>
                @else
                    <a href="{{ url('/thanh-toan') }}" data-checkout class="btn btn-primary mt-4 w-full !py-3">Thanh toán</a>
                @endif
                @if ($drawer)
                    <a href="{{ url('/gio-hang') }}" class="mt-3 block text-center text-xs text-muted underline underline-offset-4 hover:text-accent">Xem giỏ hàng</a>
                @endif
            </div>
        @endif
    </div>

    @if ($drawer)
            </div>
        </div>
    @endif
</div>
