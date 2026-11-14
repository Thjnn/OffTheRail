<div class="space-y-5">
    <section class="a-card p-4">
        <h2 class="mb-1 font-semibold">Ảnh sản phẩm</h2>
        <p class="mb-3 text-a-muted">Kéo để đổi thứ tự (ảnh đầu là ảnh bìa). Gắn ảnh với màu để trang sản phẩm đổi bộ ảnh khi khách chọn màu.</p>

        <ul wire:sort="sortImages" class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            @foreach ($images as $image)
                <li wire:key="img{{ $image->id }}" wire:sort:item="{{ $image->id }}" class="overflow-hidden rounded-md border border-a-line bg-a-surface">
                    <div class="relative">
                        <img src="{{ $image->url() }}" alt="" class="aspect-[4/5] w-full object-cover" loading="lazy">
                        <span class="absolute top-1 left-1 cursor-grab rounded bg-white/90 p-0.5 text-a-muted" title="Kéo để sắp xếp"><x-admin.icon name="grip" class="size-4" /></span>
                        <button type="button" wire:sort:ignore wire:click="deleteImage({{ $image->id }})" wire:confirm="Xoá ảnh này?"
                            class="absolute top-1 right-1 rounded bg-white/90 p-0.5 text-a-down" aria-label="Xoá ảnh"><x-admin.icon name="trash" class="size-4" /></button>
                    </div>
                    <select wire:sort:ignore wire:change="setColor({{ $image->id }}, $event.target.value === '' ? null : Number($event.target.value))"
                        class="w-full border-0 border-t border-a-line px-2 py-1 text-xs" aria-label="Gắn với màu">
                        <option value="">Mọi màu</option>
                        @foreach ($colors as $color)
                            <option value="{{ $color->id }}" @selected($image->product_color_id === $color->id)>{{ $color->name }}</option>
                        @endforeach
                    </select>
                </li>
            @endforeach
        </ul>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <input type="file" wire:model="uploads" multiple accept="image/jpeg,image/png,image/webp" class="text-sm" aria-label="Chọn ảnh">
            <button type="button" wire:click="upload" class="a-btn" wire:loading.attr="disabled" wire:target="uploads,upload">Tải ảnh lên</button>
            <span wire:loading wire:target="uploads" class="text-a-muted">Đang tải…</span>
        </div>
        @error('uploads')<p class="a-error">{{ $message }}</p>@enderror
        @foreach ($errors->get('uploads.*') as $messages)<p class="a-error">{{ $messages[0] }}</p>@endforeach
        <p class="mt-1 text-xs text-a-muted">JPG, PNG hoặc WebP, tối đa 4MB mỗi ảnh, 10 ảnh mỗi lần.</p>
    </section>

    <section class="a-card p-4">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <h2 class="font-semibold">Khối nội dung (ảnh ↔ chữ)</h2>
                <p class="text-a-muted">Hiện xen kẽ khi khách cuộn xuống trang sản phẩm.</p>
            </div>
            <button type="button" wire:click="addBlock" class="a-btn"><x-admin.icon name="plus" class="size-4" /> Thêm khối</button>
        </div>

        <ul wire:sort="sortBlocks" class="space-y-3">
            @foreach ($blocks as $i => $row)
                @php($model = $row['id'] ? $blockModels->get($row['id']) : null)
                <li wire:key="blk{{ $row['id'] ?? 'new'.$i }}" @if ($row['id']) wire:sort:item="{{ $row['id'] }}" @endif class="flex gap-3 rounded-md border border-a-line p-3">
                    <span class="cursor-grab pt-1 text-a-muted"><x-admin.icon name="grip" class="size-4" /></span>
                    <div class="w-28 shrink-0" wire:sort:ignore>
                        @if ($model?->imageUrl())
                            <img src="{{ $model->imageUrl() }}" alt="" class="mb-1 aspect-square w-full rounded object-cover">
                        @endif
                        <input type="file" wire:model="blockImages.{{ $i }}" accept="image/jpeg,image/png,image/webp" class="w-full text-xs" aria-label="Ảnh của khối">
                        @error("blockImages.$i")<p class="a-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex-1" wire:sort:ignore>
                        <textarea wire:model="blocks.{{ $i }}.body" rows="3" maxlength="2000" class="a-input" aria-label="Nội dung khối"></textarea>
                        @error("blocks.$i.body")<p class="a-error">{{ $message }}</p>@enderror
                        <div class="mt-2 flex gap-2">
                            <button type="button" wire:click="saveBlock({{ $i }})" class="a-btn a-btn-primary">Lưu khối</button>
                            @if ($row['id'])
                                <button type="button" wire:click="deleteBlock({{ $row['id'] }})" wire:confirm="Xoá khối này?" class="a-btn a-btn-danger">Xoá</button>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        @if (! $blocks)<p class="text-a-muted">Chưa có khối nào. Không có khối thì trang sản phẩm bỏ qua phần này.</p>@endif
    </section>
</div>
