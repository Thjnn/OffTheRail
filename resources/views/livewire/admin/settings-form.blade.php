<form wire:submit="save" class="max-w-4xl space-y-5">
    @if ($saved)
        <div class="rounded-md border border-a-up/30 bg-a-up/5 px-3 py-2 text-a-up" role="status">Đã lưu cài đặt.</div>
    @endif

    <section class="a-card grid gap-4 p-4 sm:grid-cols-2">
        <h2 class="font-semibold sm:col-span-2">Thương hiệu</h2>
        <div>
            <label class="a-label" for="name">Tên shop</label>
            <input id="name" wire:model="name" class="a-input" maxlength="60">
            @error('name')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="a-label" for="tagline">Khẩu hiệu</label>
            <input id="tagline" wire:model="tagline" class="a-input" maxlength="120">
            @error('tagline')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="a-label" for="accent">Màu chủ đạo</label>
            <div class="flex gap-2">
                <input type="color" wire:model.live="accent" class="h-8 w-10 rounded border border-a-line" aria-label="Chọn màu chủ đạo">
                <input id="accent" wire:model.blur="accent" class="a-input w-32 font-mono">
            </div>
            @error('accent')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="a-label" for="logo">Logo (PNG/JPG/WebP)</label>
            <div class="flex items-center gap-3">
                @if ($currentLogo)
                    <img src="{{ Storage::disk('public')->url($currentLogo) }}" alt="Logo hiện tại" class="h-8 w-auto rounded border border-a-line bg-white p-0.5">
                    <button type="button" wire:click="removeLogo" wire:confirm="Bỏ logo, dùng tên shop thay thế?" class="text-a-down hover:underline">Bỏ logo</button>
                @endif
                <input id="logo" type="file" wire:model="logo" accept="image/jpeg,image/png,image/webp" class="text-sm">
            </div>
            @error('logo')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label class="a-label" for="announcement">Thanh thông báo đầu trang</label>
            <input id="announcement" wire:model="announcement" class="a-input" maxlength="160">
            @error('announcement')<p class="a-error">{{ $message }}</p>@enderror
        </div>
    </section>

    <section class="a-card grid gap-4 p-4 sm:grid-cols-3">
        <h2 class="font-semibold sm:col-span-3">Liên hệ</h2>
        <div>
            <label class="a-label" for="hotline">Hotline</label>
            <input id="hotline" wire:model="hotline" class="a-input" inputmode="tel">
            @error('hotline')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="a-label" for="zalo_url">Link Zalo</label>
            <input id="zalo_url" wire:model="zalo_url" class="a-input" placeholder="https://zalo.me/...">
            @error('zalo_url')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="a-label" for="messenger_url">Link Messenger</label>
            <input id="messenger_url" wire:model="messenger_url" class="a-input" placeholder="https://m.me/...">
            @error('messenger_url')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-3">
            <label class="a-label" for="notify_email">Email nhận báo đơn web mới (để trống nếu không dùng)</label>
            <input id="notify_email" type="email" wire:model="notify_email" class="a-input sm:w-80">
            @error('notify_email')<p class="a-error">{{ $message }}</p>@enderror
        </div>
    </section>

    <section class="a-card space-y-4 p-4">
        <h2 class="font-semibold">Phí ship</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="a-label" for="free_shipping_threshold">Miễn phí ship khi tạm tính từ (đ)</label>
                <input id="free_shipping_threshold" type="number" min="0" step="1000" wire:model="free_shipping_threshold" class="a-input">
                @error('free_shipping_threshold')<p class="a-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="a-label" for="shipping_fee_default">Phí ship mặc định (đ)</label>
                <input id="shipping_fee_default" type="number" min="0" step="1000" wire:model="shipping_fee_default" class="a-input">
                @error('shipping_fee_default')<p class="a-error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <p class="a-label">Phí riêng theo tỉnh</p>
            @foreach ($shipping_fees as $i => $row)
                <div class="mb-2 flex flex-wrap items-start gap-2" wire:key="fee{{ $i }}">
                    <div>
                        <select wire:model="shipping_fees.{{ $i }}.province_code" class="a-input w-64" aria-label="Tỉnh">
                            <option value="">Chọn tỉnh/thành</option>
                            @foreach ($provinces as $p)<option value="{{ $p->code }}">{{ $p->name }}</option>@endforeach
                        </select>
                        @error("shipping_fees.$i.province_code")<p class="a-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <input type="number" min="0" step="1000" wire:model="shipping_fees.{{ $i }}.fee" class="a-input w-36" placeholder="Phí (đ)" aria-label="Phí ship">
                        @error("shipping_fees.$i.fee")<p class="a-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="button" wire:click="removeFeeRow({{ $i }})" class="a-btn a-btn-danger" aria-label="Bỏ dòng"><x-admin.icon name="trash" class="size-4" /></button>
                </div>
            @endforeach
            <button type="button" wire:click="addFeeRow" class="a-btn"><x-admin.icon name="plus" class="size-4" /> Thêm tỉnh</button>
        </div>
    </section>

    <section class="a-card grid gap-4 p-4 sm:grid-cols-3">
        <div class="sm:col-span-3">
            <h2 class="font-semibold">Tài khoản nhận chuyển khoản (VietQR)</h2>
            <p class="text-a-muted">Điền đủ cả ba để bật lựa chọn chuyển khoản ở trang thanh toán; để trống cả ba để tắt.</p>
        </div>
        <div>
            <label class="a-label" for="bank_bin">Mã ngân hàng (BIN, 6 số)</label>
            <input id="bank_bin" wire:model="bank_bin" class="a-input font-mono" inputmode="numeric" placeholder="970436">
            @error('bank_bin')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="a-label" for="bank_account_no">Số tài khoản</label>
            <input id="bank_account_no" wire:model="bank_account_no" class="a-input font-mono" inputmode="numeric">
            @error('bank_account_no')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="a-label" for="bank_account_name">Tên chủ tài khoản</label>
            <input id="bank_account_name" wire:model="bank_account_name" class="a-input uppercase">
            @error('bank_account_name')<p class="a-error">{{ $message }}</p>@enderror
        </div>
    </section>

    <section class="a-card grid gap-4 p-4 sm:grid-cols-2">
        <h2 class="font-semibold sm:col-span-2">Bán hàng</h2>
        <div>
            <label class="a-label" for="sizes">Các size bán (cách nhau dấu phẩy)</label>
            <input id="sizes" wire:model="sizes" class="a-input" placeholder="S, M, L, XL hoặc 29, 30, 31">
            @error('sizes')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="a-label" for="staff_max_discount_percent">Nhân viên được giảm giá tay tối đa (%)</label>
            <input id="staff_max_discount_percent" type="number" min="0" max="100" wire:model="staff_max_discount_percent" class="a-input w-28">
            @error('staff_max_discount_percent')<p class="a-error">{{ $message }}</p>@enderror
        </div>
    </section>

    <button class="a-btn a-btn-primary" wire:loading.attr="disabled">Lưu cài đặt</button>
</form>
