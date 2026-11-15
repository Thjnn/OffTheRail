<div class="mx-auto max-w-md">
    @if (! $shift)
        <div class="a-card p-6 text-center">
            <p>Không có ca nào đang mở.</p>
            <a href="{{ url('/admin/ban-tai-quay') }}" class="a-btn mt-3">Về màn bán hàng</a>
        </div>
    @elseif (! $shift->isOpen())
        <div class="a-card space-y-2 p-6">
            <p class="font-semibold text-a-up">Đã chốt ca lúc {{ $shift->closed_at->format('H:i d/m') }}.</p>
            @if ($isOwner)
                <dl class="space-y-1">
                    <div class="flex justify-between"><dt class="text-a-muted">Tiền mặt kỳ vọng</dt><dd>{{ \App\Support\Money::vnd($shift->expected_cash) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-a-muted">Đếm được</dt><dd>{{ \App\Support\Money::vnd($shift->counted_cash) }}</dd></div>
                    <div class="flex justify-between font-semibold"><dt>Chênh lệch</dt><dd>@include('admin.shifts.difference', ['value' => $shift->difference()])</dd></div>
                </dl>
            @else
                <p class="text-a-muted">Chủ shop sẽ đối chiếu số tiền bạn đếm.</p>
            @endif
            <a href="{{ url('/admin/ban-tai-quay') }}" class="a-btn mt-2">Về màn bán hàng</a>
        </div>
    @else
        <div class="a-card p-6">
            <dl class="space-y-1">
                <div class="flex justify-between"><dt class="text-a-muted">Người mở ca</dt><dd>{{ $shift->user->name }}</dd></div>
                <div class="flex justify-between"><dt class="text-a-muted">Mở lúc</dt><dd>{{ $shift->opened_at->format('H:i d/m') }}</dd></div>
                <div class="flex justify-between"><dt class="text-a-muted">Tiền đầu ca</dt><dd>{{ \App\Support\Money::vnd($shift->opening_cash) }}</dd></div>
                <div class="flex justify-between"><dt class="text-a-muted">Số đơn trong ca</dt><dd>{{ $orderCount }}</dd></div>
                @if ($isOwner)
                    <div class="flex justify-between"><dt class="text-a-muted">Tiền mặt kỳ vọng</dt><dd>{{ \App\Support\Money::vnd($shift->opening_cash + $shift->cashSales()) }}</dd></div>
                @endif
            </dl>

            <form wire:submit="close" class="mt-4 space-y-3">
                <div>
                    <label class="a-label" for="countedCash">Tiền mặt đếm được trong két (đ)</label>
                    <input id="countedCash" type="number" min="0" step="1000" inputmode="numeric" wire:model="countedCash" class="a-input" autofocus>
                    @error('countedCash')<p class="a-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="a-label" for="note">Ghi chú (tuỳ chọn)</label>
                    <input id="note" type="text" wire:model="note" class="a-input" maxlength="200">
                </div>
                <button class="a-btn a-btn-primary w-full" wire:loading.attr="disabled">Chốt ca</button>
            </form>
        </div>
    @endif
</div>
