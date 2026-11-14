<span wire:poll.30s class="ml-auto">
    @if ($count > 0)
        <span class="a-badge bg-a-down text-white" title="{{ $count }} đơn web chờ xác nhận">{{ $count }}</span>
    @endif
</span>
