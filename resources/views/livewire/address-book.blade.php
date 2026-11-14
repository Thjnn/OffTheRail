<div class="space-y-4">
    @forelse ($addresses as $address)
        <div class="rounded-card bg-surface p-5 shadow-soft" wire:key="addr-{{ $address->id }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="text-sm">
                    <p class="font-semibold">{{ $address->name }} · {{ $address->phone }}
                        @if ($address->is_default)
                            <span class="ml-1 rounded-full bg-accent/10 px-2 py-0.5 text-[11px] font-semibold text-accent">Mặc định</span>
                        @endif
                    </p>
                    <p class="text-muted">{{ $address->line() }}</p>
                </div>
                <div class="flex gap-3 text-xs">
                    @unless ($address->is_default)
                        <button type="button" wire:click="makeDefault({{ $address->id }})" class="underline underline-offset-4 hover:text-accent">Đặt mặc định</button>
                    @endunless
                    <button type="button" wire:click="edit({{ $address->id }})" class="underline underline-offset-4 hover:text-accent">Sửa</button>
                    <button type="button" wire:click="delete({{ $address->id }})" wire:confirm="Xoá địa chỉ này?" class="text-sale underline underline-offset-4">Xoá</button>
                </div>
            </div>
        </div>
    @empty
        <p class="text-sm text-muted">Bạn chưa lưu địa chỉ nào.</p>
    @endforelse

    @if ($editingId !== null)
        <form wire:submit="save" class="space-y-4 rounded-card bg-surface p-5 shadow-soft" novalidate>
            <h2 class="font-semibold">{{ $editingId > 0 ? 'Sửa địa chỉ' : 'Thêm địa chỉ' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([['name', 'Tên người nhận', 'text', 'name'], ['phone', 'Số điện thoại', 'tel', 'tel']] as [$field, $label, $type, $autocomplete])
                    <div>
                        <label for="ab-{{ $field }}" class="mb-1.5 block text-sm font-medium">{{ $label }}</label>
                        <input id="ab-{{ $field }}" type="{{ $type }}" wire:model="{{ $field }}" autocomplete="{{ $autocomplete }}"
                            class="w-full rounded-chip border border-line bg-surface px-4 py-2.5 outline-none focus:border-accent">
                        @error($field) <p class="mt-1 text-xs text-sale">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
            @include('partials.address-fields')
            <div class="flex gap-3">
                <button class="btn btn-primary">Lưu</button>
                <button type="button" wire:click="cancel" class="btn btn-outline">Huỷ</button>
            </div>
        </form>
    @elseif ($addresses->count() < \App\Models\Customer::MAX_ADDRESSES)
        <button type="button" wire:click="create" class="btn btn-outline">+ Thêm địa chỉ</button>
    @endif
</div>
