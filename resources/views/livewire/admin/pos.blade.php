<div>
    @if (! $registerShown)
        <div class="a-card mx-auto mt-10 max-w-sm p-6">
            <h2 class="text-base font-semibold">Mở ca</h2>
            <p class="mt-1 text-a-muted">Chưa có ca nào đang mở. Đếm tiền trong két rồi nhập số tiền đầu ca để bắt đầu bán.</p>
            <form wire:submit="openShift" class="mt-4 space-y-3">
                <div>
                    <label class="a-label" for="openingCash">Tiền đầu ca (đ)</label>
                    <input id="openingCash" type="number" min="0" step="1000" inputmode="numeric" wire:model="openingCash" class="a-input" autofocus>
                    @error('openingCash')<p class="a-error">{{ $message }}</p>@enderror
                </div>
                <button class="a-btn a-btn-primary w-full" wire:loading.attr="disabled">Mở ca</button>
            </form>
        </div>
    @else
        <div x-data="pos(@js($catalog), @js($maxDiscountPercent))" x-on:keydown.window="refocus($event)" class="grid gap-4 lg:grid-cols-[1fr_380px]">
            {{-- Trái: quét / tìm + lưới sản phẩm --}}
            <section class="min-w-0 space-y-3">
                @if ($shift)
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-a-muted">
                        <span>Ca của <strong class="text-a-ink">{{ $shift->user->name }}</strong> · mở lúc {{ $shift->opened_at->format('H:i d/m') }}</span>
                        <a href="{{ url('/admin/ban-tai-quay/chot-ca') }}" class="a-btn">Chốt ca</a>
                    </div>
                @else
                    {{-- Ca vừa bị chốt ở nơi khác: mở ca mới ngay tại đây, hoá đơn đang lập vẫn giữ nguyên. --}}
                    <form wire:submit="openShift" class="flex flex-wrap items-end gap-2 rounded-md border border-a-down/30 bg-a-down/5 px-3 py-2" role="alert">
                        <p class="w-full text-a-down">Ca đã được chốt. Mở ca mới để tiếp tục bán — hoá đơn đang lập vẫn còn.</p>
                        <div>
                            <label class="a-label" for="reopenCash">Tiền đầu ca (đ)</label>
                            <input id="reopenCash" type="number" min="0" step="1000" inputmode="numeric" wire:model="openingCash" class="a-input w-36">
                        </div>
                        <button class="a-btn a-btn-primary" wire:loading.attr="disabled">Mở ca</button>
                        @error('openingCash')<p class="a-error w-full">{{ $message }}</p>@enderror
                    </form>
                @endif

                <form x-on:submit.prevent="scan()" class="relative">
                    <x-admin.icon name="search" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-a-muted" />
                    <input x-ref="scan" x-model="code" type="text" autocomplete="off" autofocus
                        placeholder="Quét mã vạch hoặc gõ mã / tên rồi Enter"
                        aria-label="Quét mã vạch" class="a-input py-2.5 pl-9 text-base">
                </form>
                <p x-show="notice" x-text="notice" x-cloak class="text-a-down" role="status"></p>

                <div class="flex flex-wrap gap-1.5" role="group" aria-label="Lọc danh mục">
                    <button type="button" class="a-btn" :class="category === null && 'a-btn-primary'" x-on:click="category = null">Tất cả</button>
                    <template x-for="c in catalog.categories" :key="c.id">
                        <button type="button" class="a-btn" :class="category === c.id && 'a-btn-primary'" x-on:click="category = c.id" x-text="c.name"></button>
                    </template>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-4">
                    <template x-for="p in products()" :key="p.id">
                        <button type="button" x-on:click="pick(p)" class="a-card overflow-hidden text-left transition hover:border-accent">
                            <div class="aspect-[4/3] bg-a-soft">
                                <img x-show="p.image" :src="p.image" alt="" loading="lazy" class="size-full object-cover">
                            </div>
                            <div class="p-2">
                                <p class="line-clamp-2 font-medium" x-text="p.name"></p>
                                <p class="mt-0.5 text-xs text-a-muted"><span x-text="money(p.price)"></span> · còn <span x-text="p.stock"></span></p>
                            </div>
                        </button>
                    </template>
                </div>
                <p x-show="products().length === 0" x-cloak class="py-8 text-center text-a-muted">Không có sản phẩm phù hợp.</p>

                {{-- Hộp chọn màu / size --}}
                <div x-show="picking" x-cloak x-on:keydown.escape.window="picking = null" class="fixed inset-0 z-40 flex items-center justify-center bg-black/30 p-4" x-on:click.self="picking = null">
                    <div class="a-card w-full max-w-md p-4" role="dialog" aria-modal="true" :aria-label="picking?.name">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-semibold" x-text="picking?.name"></h3>
                            <button type="button" class="a-btn" x-on:click="picking = null">Đóng</button>
                        </div>
                        <div class="mt-3 space-y-3">
                            <template x-for="group in colorsOf(picking)" :key="group.color">
                                <div>
                                    <p class="mb-1 flex items-center gap-1.5 text-xs text-a-muted">
                                        <span class="size-3 rounded-full border border-a-line" :style="`background:${group.hex || '#fff'}`"></span>
                                        <span x-text="group.color || 'Mặc định'"></span>
                                    </p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="v in group.variants" :key="v.id">
                                            <button type="button" class="a-btn min-w-12" :disabled="v.stock <= 0" x-on:click="add(v); picking = null">
                                                <span x-text="v.size"></span>
                                                <span class="text-xs text-a-muted" x-text="`(${v.stock})`"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Phải: hoá đơn đang lập --}}
            <aside class="a-card flex flex-col self-start lg:sticky lg:top-5">
                <div class="flex items-center justify-between border-b border-a-line px-4 py-3">
                    <h2 class="font-semibold">Hoá đơn</h2>
                    <button type="button" class="text-xs text-a-muted hover:text-a-down" x-show="lines.length" x-on:click="reset()">Xoá hết</button>
                </div>

                <ul class="max-h-[40vh] divide-y divide-a-line overflow-y-auto">
                    <template x-for="line in lines" :key="line.id">
                        <li class="flex items-center gap-2 px-4 py-2" :class="line.flash && 'pos-flash'">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium" x-text="line.name"></p>
                                <p class="text-xs text-a-muted"><span x-text="[line.color, line.size].filter(Boolean).join(' / ')"></span> · <span x-text="money(line.price)"></span></p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" class="a-btn size-7 p-0" x-on:click="dec(line)" aria-label="Bớt một">−</button>
                                <span class="w-6 text-center tabular-nums" x-text="line.qty"></span>
                                <button type="button" class="a-btn size-7 p-0" x-on:click="add(line)" :disabled="line.qty >= line.stock" aria-label="Thêm một">+</button>
                            </div>
                            <span class="w-20 text-right tabular-nums" x-text="money(line.price * line.qty)"></span>
                        </li>
                    </template>
                    <li x-show="!lines.length" class="px-4 py-8 text-center text-a-muted">Quét mã hoặc chọn sản phẩm để thêm.</li>
                </ul>

                <div class="space-y-3 border-t border-a-line px-4 py-3">
                    {{-- Khách hàng (tuỳ chọn) --}}
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="a-label" for="pos-phone">SĐT khách (tuỳ chọn)</label>
                            <input id="pos-phone" type="tel" inputmode="tel" x-model="phone" x-on:change="lookupCustomer()" class="a-input">
                        </div>
                        <div>
                            <label class="a-label" for="pos-name">Tên khách</label>
                            <input id="pos-name" type="text" x-model="name" :readonly="customer !== null" class="a-input">
                        </div>
                    </div>
                    <p x-show="customer" x-cloak class="text-xs text-a-up" x-text="customer && `Khách quen · ${customer.orders_count} đơn`"></p>

                    <div class="flex items-center justify-between"><span class="text-a-muted">Tạm tính</span><span class="tabular-nums" x-text="money(subtotal())"></span></div>
                    <div class="flex items-center justify-between gap-3">
                        <label for="pos-discount" class="text-a-muted">Giảm giá
                            <template x-if="maxPercent !== null"><span class="text-xs" x-text="`(tối đa ${maxPercent}%)`"></span></template>
                        </label>
                        <input id="pos-discount" type="number" min="0" step="1000" inputmode="numeric" x-model.number="discount" class="a-input w-32 text-right">
                    </div>
                    <p x-show="discountError()" x-cloak class="a-error" x-text="discountError()"></p>
                    <div class="flex items-center justify-between text-base font-semibold"><span>Tổng</span><span class="tabular-nums" x-text="money(total())"></span></div>

                    <div class="grid grid-cols-2 gap-1.5" role="radiogroup" aria-label="Hình thức thanh toán">
                        <button type="button" class="a-btn" :class="method === 'cash' && 'a-btn-primary'" x-on:click="method = 'cash'">Tiền mặt</button>
                        <button type="button" class="a-btn" :class="method === 'qr' && 'a-btn-primary'" x-on:click="showQr()">Chuyển khoản QR</button>
                    </div>

                    <div x-show="method === 'cash'" class="space-y-2">
                        <div class="flex items-center justify-between gap-3">
                            <label for="pos-cash" class="text-a-muted">Khách đưa</label>
                            <input id="pos-cash" type="number" min="0" step="1000" inputmode="numeric" x-model.number="cash" class="a-input w-32 text-right">
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="amount in quickCash()" :key="amount">
                                <button type="button" class="a-btn px-2 text-xs" x-on:click="cash = amount" x-text="money(amount)"></button>
                            </template>
                        </div>
                        <div class="flex items-center justify-between"><span class="text-a-muted">Tiền thối</span><span class="font-semibold tabular-nums" :class="change() < 0 && 'text-a-down'" x-text="money(Math.max(0, change()))"></span></div>
                    </div>

                    <div x-show="method === 'qr'" x-cloak class="text-center">
                        @if ($qrReady)
                            <img x-show="qrUrl" :src="qrUrl" alt="Mã VietQR" class="mx-auto w-48">
                            <p class="mt-1 text-xs text-a-muted">Khách quét mã, thấy tiền về thì bấm "Đã nhận tiền".</p>
                        @else
                            <p class="text-xs text-a-muted">Chưa cài tài khoản ngân hàng ở Cài đặt nên không hiện được mã QR. Vẫn có thể ghi nhận khi khách đã chuyển.</p>
                        @endif
                    </div>

                    @error('checkout')<p class="rounded-md border border-a-down/30 bg-a-down/5 px-3 py-2 text-a-down" role="alert">{{ $message }}</p>@enderror

                    <button type="button" class="a-btn a-btn-primary w-full py-2.5 text-base" x-on:click="pay()" :disabled="!canPay()"
                        x-text="busy ? 'Đang lưu…' : (method === 'cash' ? 'Thanh toán' : 'Đã nhận tiền')"></button>
                </div>
            </aside>
        </div>
    @endif
</div>
