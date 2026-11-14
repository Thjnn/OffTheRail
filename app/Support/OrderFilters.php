<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/** Bộ lọc đơn dùng chung cho bảng đơn và file Excel. Giá trị rác bị bỏ qua. */
class OrderFilters
{
    /** @param array{status?: ?string, channel?: ?string, from?: ?string, to?: ?string, staff?: mixed, q?: ?string} $filters */
    public static function apply(Builder $query, array $filters): Builder
    {
        if ($status = OrderStatus::tryFrom((string) ($filters['status'] ?? ''))) {
            $query->where('status', $status);
        }

        if (array_key_exists((string) ($filters['channel'] ?? ''), Order::CHANNELS)) {
            $query->where('channel', $filters['channel']);
        }

        if ($from = self::date($filters['from'] ?? null)) {
            $query->where('created_at', '>=', $from->startOfDay());
        }

        if ($to = self::date($filters['to'] ?? null)) {
            $query->where('created_at', '<=', $to->endOfDay());
        }

        if (ctype_digit((string) ($filters['staff'] ?? ''))) {
            $query->where('staff_id', (int) $filters['staff']);
        }

        $q = trim((string) ($filters['q'] ?? ''));

        if ($q !== '') {
            $phone = Phone::normalize($q);
            $query->where(function (Builder $w) use ($q, $phone) {
                $w->where('code', strtoupper($q))
                    ->orWhere('external_code', strtoupper($q))
                    ->orWhere('customer_name', 'like', '%'.$q.'%');

                if ($phone !== null) {
                    $w->orWhere('customer_phone', $phone);
                }
            });
        }

        return $query;
    }

    private static function date(?string $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
