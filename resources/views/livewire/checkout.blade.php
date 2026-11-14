@use('App\Support\Money')

<form wire:submit="place" class="grid gap-8 lg:grid-cols-[1fr_380px]" novalidate>
    <div class="space-y-8">
        @error('cart')
            <p class="rounded-chip bg-sale/10 px-4 py-3 text-sm text-sale" role="alert">{{ $message }}</p>
        @enderror

        {{-- Ô bẫy bot: người dùng thật không thấy và không tab tới được --}}
        <div class="hidden" aria-hidden="true">
            <label>Website <input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <section class="rounded-card bg-surface p-5 shadow-soft sm:p-6">
            <h2 class="mb-4 font-semibold">Người nhận</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([['name', 'Họ tên', 'text', 'name'], ['phone', 'Số điện thoại', 'tel', 'tel'], ['email', 'Email (không bắt buộc)', 'email', 'email']] as [$field, $label, $type, $autocomplete])
                    <div class="{{ $field === 'email' ? 'sm:col-span-2' : '' }}">
                        <label for="{{ $field }}" class="mb-1.5 block text-sm font-medium">{{ $label }}</label>
                        <input id="{{ $field }}" type="{{ $type }}" wire:model="{{ $field }}" autocomplete="{{ $autocomplete }}"
                            class="w-full rounded-chip border border-line bg-surface px-4 py-2.5 outline-none focus:border-accent">
                        @error($field) <p class="mt-1 text-xs text-sale">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-card bg-surface p-5 shadow-soft sm:p-6">
            <h2 class="mb-4 font-semibold">Địa chỉ giao hàng</h2>
            @includeWhen(isset($savedAddresses) && $savedAddresses->isNotEmpty(), 'partials.saved-addresses')
            @include('partials.address-fields')
            <div class="mt-4">
                <label for="note" class="mb-1.5 block text-sm font-medium">Ghi chú (không bắt buộc)</label>
                <textarea id="note" wire:model="note" rows="2" placeholder="Giao giờ hành chính, gọi trước khi giao…"
                    class="w-full rounded-chip border border-line bg-surface px-4 py-2.5 outline-none focus:border-accent"></textarea>
                @error('note') <p class="mt-1 text-xs text-sale">{{ $message }}</p> @enderror
            </div>
            @auth('web')
                <label class="mt-4 flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="saveAddress" class="size-4 accent-[var(--color-accent)]"> Lưu địa chỉ này cho lần sau
                </label>
            @endauth
        </section>

        <section class="rounded-card bg-surface p-5 shadow-soft sm:p-6">
            <h2 class="mb-4 font-semibold">Thanh toán</h2>
            <div class="space-y-3" role="radiogroup" aria-label="Phương thức thanh toán">
                <label class="flex cursor-pointer items-start gap-3 rounded-chip border border-line p-4 has-[:checked]:border-accent">
                    <input type="radio" wire:model.live="paymentMethod" value="cod" class="mt-1 accent-[var(--color-accent)]">
                    <span><span class="block font-medium">Thanh toán khi nhận hàng (COD)</span>
                        <span class="text-xs text-muted">Trả tiền mặt cho người giao hàng.</span></span>
                </label>
                @if ($canTransfer)
                    <label class="flex cursor-pointer items-start gap-3 rounded-chip border border-line p-4 has-[:checked]:border-accent">
                        <input type="radio" wire:model.live="paymentMethod" value="bank_transfer" class="mt-1 accent-[var(--color-accent)]">
                        <span><span class="block font-medium">Chuyển khoản (VietQR)</span>
                            <span class="text-xs text-muted">Quét mã QR sau khi đặt, đã có sẵn số tiền và nội dung.</span></span>
                    </label>
                @endif
            </div>
            @error('paymentMethod') <p class="mt-2 text-xs text-sale">{{ $message }}</p> @enderror
        </section>
    </div>

    <aside class="h-fit rounded-card bg-surface p-5 shadow-soft sm:p-6 lg:sticky lg:top-24">
        <h2 class="mb-4 font-semibold">Đơn hàng</h2>
        <ul class="divide-y divide-line text-sm">
            @foreach ($lines as $line)
                <li class="flex gap-3 py-3" wire:key="sum-{{ $line->variant->id }}">
                    <div class="h-16 w-14 shrink-0 overflow-hidden rounded-chip bg-surface-2">
                        @if ($line->imageUrl())
                            <img src="{{ $line->imageUrl() }}" alt="" class="size-full object-cover" loading="lazy">
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ $line->variant->product->name }}</p>
                        <p class="text-xs text-muted">{{ $line->variant->color?->name }} · {{ $line->variant->size }} · SL {{ $line->qty }}</p>
                        @unless ($line->isAvailable())
                            <p class="text-xs font-semibold text-sale">{{ $line->variant->stock > 0 ? 'Chỉ còn '.$line->variant->stock : 'Hết hàng' }}</p>
                        @endunless
                    </div>
                    <span class="tabular-nums">{{ Money::vnd($line->total()) }}</span>
                </li>
            @endforeach
        </ul>
        <dl class="mt-4 space-y-2 border-t border-line pt-4 text-sm">
            <div class="flex justify-between"><dt class="text-muted">Tạm tính</dt><dd class="tabular-nums">{{ Money::vnd($subtotal) }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">Phí giao hàng</dt>
                <dd class="tabular-nums">{{ $shipping === 0 ? 'Miễn phí' : Money::vnd($shipping) }}</dd></div>
            <div class="flex justify-between border-t border-line pt-3 text-base font-semibold"><dt>Tổng cộng</dt><dd class="tabular-nums">{{ Money::vnd($total) }}</dd></div>
        </dl>
        <button type="submit" class="btn btn-primary mt-5 w-full !py-3" wire:loading.attr="disabled" wire:target="place">
            <span wire:loading.remove wire:target="place">Đặt hàng</span>
            <span wire:loading wire:target="place">Đang đặt…</span>
        </button>
        <a href="{{ url('/gio-hang') }}" class="mt-3 block text-center text-xs text-muted underline underline-offset-4 hover:text-accent">Quay lại giỏ hàng</a>
    </aside>
</form>
