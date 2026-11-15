<?php

namespace App\Livewire\Admin;

use App\Exceptions\OrderActionException;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/** Chốt ca. Nhân viên chốt "mù": không thấy tiền kỳ vọng hay chênh lệch; chủ shop thấy cả hai. */
class ShiftClose extends Component
{
    public ?int $countedCash = null;

    public string $note = '';

    public ?int $closedId = null;

    public function close(ShiftService $shifts): void
    {
        $this->validate([
            'countedCash' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:200'],
        ], [], ['countedCash' => 'tiền mặt đếm được', 'note' => 'ghi chú']);

        $shift = $shifts->current();

        if (! $shift) {
            $this->addError('countedCash', 'Không có ca nào đang mở.');

            return;
        }

        try {
            $this->closedId = $shifts->close($shift, $this->countedCash, auth('admin')->user(), $this->note ?: null)->id;
        } catch (OrderActionException $e) {
            $this->addError('countedCash', $e->getMessage());
        }
    }

    public function render(ShiftService $shifts): View
    {
        $shift = $this->closedId ? Shift::with('user')->find($this->closedId) : $shifts->current();

        return view('livewire.admin.shift-close', [
            'shift' => $shift,
            'isOwner' => auth('admin')->user()->isOwner(),
            'orderCount' => $shift?->orders()->where('channel', 'pos')->count() ?? 0,
        ]);
    }
}
