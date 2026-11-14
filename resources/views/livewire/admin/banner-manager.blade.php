<section class="a-card p-4">
    <h2 class="mb-1 font-semibold">Banner trang chủ</h2>
    <p class="mb-3 text-a-muted">Kéo để đổi thứ tự. Ảnh ngang, nên rộng từ 1600px.</p>

    <ul wire:sort="sort" class="space-y-3">
        @foreach ($banners as $banner)
            <li wire:key="b{{ $banner->id }}" wire:sort:item="{{ $banner->id }}" class="flex flex-wrap gap-3 rounded-md border border-a-line p-3 {{ $banner->is_active ? '' : 'opacity-60' }}">
                <span class="cursor-grab pt-1 text-a-muted"><x-admin.icon name="grip" class="size-4" /></span>
                <div class="w-40 shrink-0" wire:sort:ignore>
                    <img src="{{ $banner->imageUrl() }}" alt="" class="aspect-video w-full rounded object-cover">
                    <input type="file" wire:model="replacements.{{ $banner->id }}" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full text-xs" aria-label="Đổi ảnh">
                    @error("replacements.$banner->id")<p class="a-error">{{ $message }}</p>@enderror
                </div>
                <div class="grid flex-1 gap-2 sm:grid-cols-2" wire:sort:ignore>
                    <input wire:model="rows.{{ $banner->id }}.title" class="a-input" placeholder="Tiêu đề" aria-label="Tiêu đề">
                    <input wire:model="rows.{{ $banner->id }}.subtitle" class="a-input" placeholder="Dòng phụ" aria-label="Dòng phụ">
                    <div>
                        <input wire:model="rows.{{ $banner->id }}.link" class="a-input" placeholder="/san-pham hoặc https://..." aria-label="Link">
                        @error("rows.$banner->id.link")<p class="a-error">{{ $message }}</p>@enderror
                    </div>
                    <input wire:model="rows.{{ $banner->id }}.link_label" class="a-input" placeholder="Chữ trên nút" aria-label="Chữ trên nút">
                    <div class="flex gap-2 sm:col-span-2">
                        <button type="button" wire:click="update({{ $banner->id }})" class="a-btn a-btn-primary">Lưu</button>
                        <button type="button" wire:click="toggle({{ $banner->id }})" class="a-btn">{{ $banner->is_active ? 'Ẩn' : 'Hiện' }}</button>
                        <button type="button" wire:click="delete({{ $banner->id }})" wire:confirm="Xoá banner này?" class="a-btn a-btn-danger ml-auto">Xoá</button>
                    </div>
                </div>
            </li>
        @endforeach
    </ul>

    <form wire:submit="create" class="mt-4 grid gap-2 rounded-md border border-dashed border-a-line p-3 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <input type="file" wire:model="image" accept="image/jpeg,image/png,image/webp" class="text-sm" aria-label="Ảnh banner mới">
            @error('image')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <input wire:model="title" class="a-input" placeholder="Tiêu đề" aria-label="Tiêu đề banner mới">
        <input wire:model="subtitle" class="a-input" placeholder="Dòng phụ" aria-label="Dòng phụ banner mới">
        <div>
            <input wire:model="link" class="a-input" placeholder="/san-pham hoặc https://..." aria-label="Link banner mới">
            @error('link')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <input wire:model="link_label" class="a-input" placeholder="Chữ trên nút" aria-label="Chữ trên nút banner mới">
        <div class="sm:col-span-2"><button class="a-btn"><x-admin.icon name="plus" class="size-4" /> Thêm banner</button></div>
    </form>
</section>
