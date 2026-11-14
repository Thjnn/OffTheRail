<?php

namespace App\Support;

class Phone
{
    /** Đưa SĐT về dạng 0xxxxxxxxx; trả null nếu không phải số di động/bàn 10 số hợp lệ. */
    public static function normalize(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);

        if (strlen($digits) === 11 && str_starts_with($digits, '84')) {
            $digits = '0'.substr($digits, 2);
        }

        return preg_match('/^0\d{9}$/', $digits) ? $digits : null;
    }
}
