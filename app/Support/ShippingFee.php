<?php

namespace App\Support;

class ShippingFee
{
    /** Miễn phí khi tạm tính đạt ngưỡng; ngược lại lấy phí theo tỉnh, không có thì dùng mức mặc định. */
    public static function for(int $subtotal, ?string $provinceCode): int
    {
        if ($subtotal >= (int) shop('free_shipping_threshold')) {
            return 0;
        }

        $fees = shop('shipping_fees');
        $fee = is_array($fees) ? ($fees[$provinceCode ?? ''] ?? null) : null;

        return is_int($fee) && $fee >= 0 ? $fee : (int) shop('shipping_fee_default');
    }
}
