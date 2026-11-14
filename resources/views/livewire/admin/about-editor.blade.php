<section class="a-card p-4">
    <h2 class="mb-1 font-semibold">Trang giới thiệu</h2>
    <p class="mb-3 text-a-muted">Mỗi đoạn cách nhau một dòng trống. Để trống thì trang dùng nội dung mẫu có sẵn.</p>
    <form wire:submit="save">
        <textarea wire:model="body" rows="8" maxlength="5000" class="a-input" aria-label="Nội dung trang giới thiệu"></textarea>
        @error('body')<p class="a-error">{{ $message }}</p>@enderror
        <div class="mt-2 flex items-center gap-3">
            <button class="a-btn a-btn-primary">Lưu</button>
            <a href="{{ url('/gioi-thieu') }}" target="_blank" class="text-accent hover:underline">Xem trang ↗</a>
            @if ($saved)<span class="text-a-up">Đã lưu.</span>@endif
        </div>
    </form>
</section>
