<?php

namespace App\Support;

use App\Models\Order;

class VietQr
{
    public static function isConfigured(): bool
    {
        return filled(shop('bank_bin')) && filled(shop('bank_account_no'));
    }

    /**
     * Ảnh mã VietQR có sẵn số tiền và nội dung = mã đơn.
     * ponytail: phụ thuộc dịch vụ img.vietqr.io; muốn bỏ thì tự dựng chuỗi EMVCo + thư viện vẽ QR.
     */
    public static function url(Order $order): ?string
    {
        if (! self::isConfigured()) {
            return null;
        }

        return sprintf(
            'https://img.vietqr.io/image/%s-%s-compact2.png?amount=%d&addInfo=%s&accountName=%s',
            urlencode((string) shop('bank_bin')),
            urlencode((string) shop('bank_account_no')),
            $order->total,
            urlencode($order->code),
            urlencode((string) shop('bank_account_name')),
        );
    }
}
