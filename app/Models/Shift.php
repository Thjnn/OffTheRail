<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Ca bán tại quầy: mở với tiền đầu ca, chốt bằng tiền đếm được, so với tiền mặt kỳ vọng. */
class Shift extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'integer',
            'counted_cash' => 'integer',
            'expected_cash' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('closed_at');
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    /** Tiền mặt bán được trong ca (đơn đã huỷ/hoàn không tính: tiền đã trả lại khách từ két). */
    public function cashSales(): int
    {
        return $this->paidTotal('cash');
    }

    public function qrSales(): int
    {
        return $this->paidTotal('qr');
    }

    /** Âm = thiếu tiền, dương = dư. Null khi chưa chốt. */
    public function difference(): ?int
    {
        return $this->closed_at === null ? null : $this->counted_cash - $this->expected_cash;
    }

    private function paidTotal(string $method): int
    {
        return (int) $this->orders()->where('channel', 'pos')->where('payment_method', $method)->where('payment_status', 'paid')->sum('total');
    }
}
