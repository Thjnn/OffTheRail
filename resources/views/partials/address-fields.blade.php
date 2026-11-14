{{-- Dùng trong component có trait HasAddressFields --}}
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="provinceCode" class="mb-1.5 block text-sm font-medium">Tỉnh / Thành phố</label>
        <select id="provinceCode" wire:model.live="provinceCode" autocomplete="address-level1"
            class="w-full rounded-chip border border-line bg-surface px-4 py-2.5 outline-none focus:border-accent">
            <option value="">Chọn tỉnh/thành</option>
            @foreach ($this->provinces as $province)
                <option value="{{ $province->code }}">{{ $province->name }}</option>
            @endforeach
        </select>
        @error('provinceCode') <p class="mt-1 text-xs text-sale">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="wardCode" class="mb-1.5 block text-sm font-medium">Phường / Xã</label>
        <select id="wardCode" wire:model="wardCode" @disabled($provinceCode === '') autocomplete="address-level2"
            class="w-full rounded-chip border border-line bg-surface px-4 py-2.5 outline-none focus:border-accent disabled:opacity-50">
            <option value="">Chọn phường/xã</option>
            @foreach ($this->wards as $ward)
                <option value="{{ $ward->code }}" wire:key="ward-{{ $ward->code }}">{{ $ward->name }}</option>
            @endforeach
        </select>
        @error('wardCode') <p class="mt-1 text-xs text-sale">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="street" class="mb-1.5 block text-sm font-medium">Số nhà, tên đường</label>
        <input id="street" type="text" wire:model="street" autocomplete="street-address"
            class="w-full rounded-chip border border-line bg-surface px-4 py-2.5 outline-none focus:border-accent">
        @error('street') <p class="mt-1 text-xs text-sale">{{ $message }}</p> @enderror
    </div>
</div>
