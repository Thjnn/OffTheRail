<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/** Số đơn web chờ xác nhận cạnh menu "Đơn hàng"; tự hỏi lại mỗi 30 giây, có đơn mới thì báo cho JS phát âm. */
class PendingBadge extends Component
{
    public int $lastMaxId = 0;

    public function mount(): void
    {
        $this->lastMaxId = (int) Order::where('channel', 'web')->max('id');
    }

    public function render(): View
    {
        $maxId = (int) Order::where('channel', 'web')->max('id');
        $count = Order::where('channel', 'web')->where('status', OrderStatus::Pending)->count();

        if ($maxId > $this->lastMaxId) {
            $this->lastMaxId = $maxId;
            $this->dispatch('new-web-order', count: $count);
        }

        return view('livewire.admin.pending-badge', ['count' => $count]);
    }
}
