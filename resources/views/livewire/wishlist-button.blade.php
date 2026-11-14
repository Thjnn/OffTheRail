<button type="button" wire:click="toggle" aria-pressed="{{ $liked ? 'true' : 'false' }}"
    aria-label="{{ $liked ? 'Bỏ khỏi yêu thích' : 'Thêm vào yêu thích' }}" title="{{ $liked ? 'Bỏ khỏi yêu thích' : 'Thêm vào yêu thích' }}"
    class="icon-btn shrink-0 {{ $liked ? 'text-sale' : '' }}">
    <svg class="size-[18px]" viewBox="0 0 24 24" fill="{{ $liked ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.7">
        <path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z"/>
    </svg>
</button>
