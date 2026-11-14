<section class="a-card p-4">
    <h2 class="mb-1 font-semibold">Bảng size theo danh mục</h2>
    <p class="mb-3 text-a-muted">Dòng đầu là tiêu đề cột, mỗi dòng sau là một size, các ô cách nhau bởi dấu <code>|</code>. Ví dụ: <code>S | 96 | 68</code>. Để trống thì trang sản phẩm ẩn link Bảng size.</p>
    <form wire:submit="save" class="space-y-2">
        <select wire:model.live="categoryId" class="a-input w-56" aria-label="Danh mục">
            @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
        </select>
        <textarea wire:model="text" rows="7" class="a-input font-mono" aria-label="Bảng size"></textarea>
        @error('text')<p class="a-error">{{ $message }}</p>@enderror
        <div class="flex items-center gap-3">
            <button class="a-btn a-btn-primary">Lưu bảng size</button>
            @if ($saved)<span class="text-a-up">Đã lưu.</span>@endif
        </div>
    </form>
</section>
