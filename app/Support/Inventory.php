<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use App\Models\ProductVariant;
use App\Models\StockMovement;

/** Mọi thay đổi tồn kho đi qua đây, mỗi lần ghi một dòng stock_movements. */
class Inventory
{
    /**
     * Trừ kho có điều kiện: chỉ trừ khi còn đủ hàng, nên web và quầy không thể cùng bán chiếc cuối.
     * Trả false (không đổi gì) nếu không đủ hàng.
     */
    public static function take(int $variantId, int $qty, string $reason, ?Order $order = null, ?int $userId = null, ?string $note = null): bool
    {
        $affected = ProductVariant::whereKey($variantId)->where('stock', '>=', $qty)->decrement('stock', $qty);

        if ($affected === 0) {
            return false;
        }

        self::log($variantId, -$qty, $reason, $order, $userId, $note);

        return true;
    }

    public static function putBack(int $variantId, int $qty, string $reason, ?Order $order = null, ?int $userId = null, ?string $note = null): void
    {
        if (ProductVariant::whereKey($variantId)->increment('stock', $qty) > 0) {
            self::log($variantId, $qty, $reason, $order, $userId, $note);
        }
    }

    /**
     * Điều chỉnh kho tay (chỉ chủ shop): `import` cộng thêm $qty (≥ 1), `count` đặt tồn = $qty (≥ 0, ghi lý do `adjust`).
     * Chênh lệch tính trên số đã khoá, nên đơn bán chen giữa không bị ghi đè sai. Trả tồn mới.
     *
     * @throws InvalidArgumentException tham số sai hoặc thiếu lý do
     */
    public static function adjust(int $variantId, string $mode, int $qty, string $note, User $by): int
    {
        $note = trim($note);

        if ($note === '' || ! in_array($mode, ['import', 'count'], true) || ($mode === 'import' ? $qty < 1 : $qty < 0)) {
            throw new InvalidArgumentException('Điều chỉnh kho không hợp lệ.');
        }

        return DB::transaction(function () use ($variantId, $mode, $qty, $note, $by) {
            $variant = ProductVariant::whereKey($variantId)->lockForUpdate()->firstOrFail();
            $old = $variant->stock;
            $new = $mode === 'import' ? $old + $qty : $qty;

            if ($new === $old) {
                return $old;
            }

            $variant->update(['stock' => $new]);
            self::log($variant->id, $new - $old, $mode === 'import' ? 'import' : 'adjust', null, $by->id, $note);
            ActivityLog::record('stock.adjust', $variant, ['stock' => $old], ['stock' => $new, 'note' => $note], $by);

            return $new;
        });
    }

    private static function log(int $variantId, int $change, string $reason, ?Order $order, ?int $userId, ?string $note): void
    {
        StockMovement::create([
            'variant_id' => $variantId, 'change' => $change, 'reason' => $reason, 'order_id' => $order?->id,
            'user_id' => $userId, 'note' => $note,
        ]);
    }
}
