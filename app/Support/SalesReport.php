<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Số liệu bán hàng trong một khoảng ngày. Doanh thu = tiền đã nhận (theo paid_at);
 * đơn đã hoàn tiền tự rơi ra vì payment_status thành `refunded`.
 */
class SalesReport
{
    public const CHANNELS = ['web', 'pos', 'shopee'];

    private CarbonImmutable $from;

    private CarbonImmutable $to;

    public function __construct(CarbonInterface $from, CarbonInterface $to, private ?int $staffId = null)
    {
        $this->from = CarbonImmutable::instance($from)->startOfDay();
        $this->to = CarbonImmutable::instance($to)->endOfDay();
    }

    public function revenue(): int
    {
        return (int) $this->paid()->sum('total');
    }

    public function orderCount(): int
    {
        return $this->scoped(Order::query())
            ->whereBetween('created_at', [$this->from, $this->to])
            ->where('status', '!=', OrderStatus::Cancelled)
            ->count();
    }

    /** @return array{labels: list<string>, series: array{web: list<int>, pos: list<int>, shopee: list<int>}} */
    public function daily(): array
    {
        $days = [];
        for ($d = $this->from; $d->lte($this->to); $d = $d->addDay()) {
            $days[$d->format('Y-m-d')] = $d->format('d/m');
        }

        $series = array_fill_keys(self::CHANNELS, array_fill_keys(array_keys($days), 0));

        // Gom bằng PHP để chạy được cả MySQL lẫn SQLite (test).
        foreach ($this->paid()->get(['channel', 'total', 'paid_at']) as $order) {
            $key = $order->paid_at->format('Y-m-d');
            if (isset($series[$order->channel][$key])) {
                $series[$order->channel][$key] += $order->total;
            }
        }

        return [
            'labels' => array_values($days),
            'series' => array_map('array_values', $series),
        ];
    }

    public function previous(): self
    {
        $days = (int) $this->from->diffInDays($this->to->startOfDay()) + 1;

        return new self($this->from->subDays($days), $this->from->subDay(), $this->staffId);
    }

    public static function change(int $now, int $before): ?int
    {
        return $before === 0 ? null : (int) round(($now - $before) / $before * 100);
    }

    private function paid(): Builder
    {
        return $this->scoped(Order::query())
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$this->from, $this->to]);
    }

    private function scoped(Builder $query): Builder
    {
        return $query->when($this->staffId !== null, fn (Builder $q) => $q->where('staff_id', $this->staffId));
    }
}
