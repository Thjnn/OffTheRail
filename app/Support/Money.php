<?php

namespace App\Support;

final class Money
{
    public static function vnd(int $amount): string
    {
        return number_format($amount, 0, ',', '.').'đ';
    }
}
