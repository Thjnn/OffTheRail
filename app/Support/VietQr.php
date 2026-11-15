<?php

namespace App\Support;

use App\Models\Order;

class VietQr
{
    public static function isConfigured(): bool
    {
        return filled(shop('bank_bin')) && filled(shop('bank_account_no'));
    }

    /** Ảnh mã VietQR của đơn: số tiền = tổng đơn, nội dung = mã đơn. */
    public static function url(Order $order): ?string
    {
        return self::image($order->total, $order->code);
    }

    /**
     * Ảnh mã VietQR có sẵn số tiền và nội dung chuyển khoản. Null khi shop chưa cài tài khoản.
     * ponytail: phụ thuộc dịch vụ img.vietqr.io; muốn bỏ thì tự dựng chuỗi EMVCo + thư viện vẽ QR.
     */
    public static function image(int $amount, string $info): ?string
    {
        if (! self::isConfigured()) {
            return null;
        }

        return sprintf(
            'https://img.vietqr.io/image/%s-%s-compact2.png?amount=%d&addInfo=%s&accountName=%s',
            urlencode((string) shop('bank_bin')),
            urlencode((string) shop('bank_account_no')),
            $amount,
            urlencode($info),
            urlencode((string) shop('bank_account_name')),
        );
    }
}
