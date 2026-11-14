<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;

class OrderStatusLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['to' => OrderStatus::class];
    }
}
