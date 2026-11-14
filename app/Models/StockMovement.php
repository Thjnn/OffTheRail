<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public const REASONS = ['sale' => 'Bán', 'cancel' => 'Huỷ đơn', 'return' => 'Hoàn đơn', 'adjust' => 'Kiểm kho', 'import' => 'Nhập hàng'];

    protected $guarded = [];

    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    protected function casts(): array
    {
        return ['change' => 'integer'];
    }
}
