<?php

namespace App\Support;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockMovement;

/** Mọi thay đổi tồn kho đi qua đây, mỗi lần ghi một dòng stock_movements. */
class Inventory
{
    /**
     * Trừ kho có điều kiện: chỉ trừ khi còn đủ hàng, nên web và quầy không thể cùng bán chiếc cuối.
     * Trả false (không đổi gì) nếu không đủ hàng.
     */
    public static function take(int $variantId, int $qty, string $reason, ?Order $order = null): bool
    {
        $affected = ProductVariant::whereKey($variantId)->where('stock', '>=', $qty)->decrement('stock', $qty);

        if ($affected === 0) {
            return false;
        }

        self::log($variantId, -$qty, $reason, $order);

        return true;
    }

    public static function putBack(int $variantId, int $qty, string $reason, ?Order $order = null): void
    {
        if (ProductVariant::whereKey($variantId)->increment('stock', $qty) > 0) {
            self::log($variantId, $qty, $reason, $order);
        }
    }

    private static function log(int $variantId, int $change, string $reason, ?Order $order): void
    {
        StockMovement::create([
            'variant_id' => $variantId, 'change' => $change, 'reason' => $reason, 'order_id' => $order?->id,
        ]);
    }
}
