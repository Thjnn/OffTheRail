<section class="a-card p-4">
    <h2 class="mb-1 font-semibold">Khối trang chủ</h2>
    <p class="mb-3 text-a-muted">Thứ tự hiển thị dưới banner. Kéo để sắp xếp, ẩn khối không dùng.</p>

    <ul wire:sort="sort" class="space-y-3">
        @foreach ($sections as $section)
            <li wire:key="s{{ $section->id }}" wire:sort:item="{{ $section->id }}" class="flex gap-3 rounded-md border border-a-line p-3 {{ $section->is_active ? '' : 'opacity-60' }}">
                <span class="cursor-grab pt-1 text-a-muted"><x-admin.icon name="grip" class="size-4" /></span>
                <div class="grid flex-1 gap-2 sm:grid-cols-2" wire:sort:ignore>
                    <p class="text-xs font-medium text-a-muted uppercase sm:col-span-2">{{ \App\Livewire\Admin\HomeSectionManager::TYPE_LABELS[$section->type] ?? $section->type }}</p>
                    <input wire:model="rows.{{ $section->id }}.title" class="a-input" placeholder="Tiêu đề khối" aria-label="Tiêu đề khối">

                    @if (in_array($section->type, ['new_arrivals', 'featured']))
                        <div>
                            <input type="number" min="1" max="24" wire:model="rows.{{ $section->id }}.limit" class="a-input w-28" aria-label="Số sản phẩm">
                            @error("rows.$section->id.limit")<p class="a-error">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    @if ($section->type === 'story')
                        <input wire:model="rows.{{ $section->id }}.eyebrow" class="a-input" placeholder="Dòng nhỏ phía trên" aria-label="Dòng nhỏ phía trên">
                        <textarea wire:model="rows.{{ $section->id }}.body" rows="3" class="a-input sm:col-span-2" placeholder="Nội dung" aria-label="Nội dung"></textarea>
                        <div>
                            <input wire:model="rows.{{ $section->id }}.link" class="a-input" placeholder="/gioi-thieu" aria-label="Link">
                            @error("rows.$section->id.link")<p class="a-error">{{ $message }}</p>@enderror
                        </div>
                        <input wire:model="rows.{{ $section->id }}.link_label" class="a-input" placeholder="Chữ trên link" aria-label="Chữ trên link">
                    @endif

                    <div class="flex gap-2 sm:col-span-2">
                        <button type="button" wire:click="save({{ $section->id }})" class="a-btn a-btn-primary">Lưu</button>
                        <button type="button" wire:click="toggle({{ $section->id }})" class="a-btn">{{ $section->is_active ? 'Ẩn' : 'Hiện' }}</button>
                        <button type="button" wire:click="delete({{ $section->id }})" wire:confirm="Xoá khối này?" class="a-btn a-btn-danger ml-auto">Xoá</button>
                    </div>
                </div>
            </li>
        @endforeach
    </ul>

    <div class="mt-4 flex items-center gap-2">
        <select wire:model="newType" class="a-input w-48" aria-label="Loại khối mới">
            @foreach (\App\Livewire\Admin\HomeSectionManager::TYPE_LABELS as $type => $label)<option value="{{ $type }}">{{ $label }}</option>@endforeach
        </select>
        <button type="button" wire:click="add" class="a-btn"><x-admin.icon name="plus" class="size-4" /> Thêm khối</button>
        @error('newType')<p class="a-error">{{ $message }}</p>@enderror
    </div>
</section>
