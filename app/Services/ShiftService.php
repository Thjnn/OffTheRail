<?php

namespace App\Services;

use App\Exceptions\OrderActionException;
use App\Models\ActivityLog;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    /** Ca đang mở của shop (tối đa một). */
    public function current(): ?Shift
    {
        return Shift::open()->with('user')->first();
    }

    /** @throws OrderActionException đã có ca mở hoặc tiền đầu ca âm */
    public function open(int $openingCash, User $by): Shift
    {
        if ($openingCash < 0) {
            throw new OrderActionException('Tiền đầu ca không được âm.');
        }

        if ($current = $this->current()) {
            throw new OrderActionException("Đang có ca mở của {$current->user->name}.");
        }

        try {
            return Shift::create(['user_id' => $by->id, 'opened_at' => now(), 'opening_cash' => $openingCash, 'open_lock' => 1]);
        } catch (UniqueConstraintViolationException) {
            throw new OrderActionException('Vừa có người khác mở ca.'); // hai người bấm cùng lúc
        }
    }

    /** @throws OrderActionException ca đã chốt */
    public function close(Shift $shift, int $countedCash, User $by, ?string $note = null): Shift
    {
        return DB::transaction(function () use ($shift, $countedCash, $by, $note) {
            $locked = Shift::whereKey($shift->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isOpen()) {
                throw new OrderActionException('Ca này đã chốt.');
            }

            $locked->fill([
                'expected_cash' => $locked->opening_cash + $locked->cashSales(),
                'counted_cash' => max(0, $countedCash),
                'closed_at' => now(),
                'closed_by' => $by->id,
                'note' => $note,
                'open_lock' => null,
            ])->save();

            ActivityLog::record('shift.close', $locked, [], ['expected' => $locked->expected_cash, 'counted' => $locked->counted_cash], $by);

            return $locked;
        });
    }
}
