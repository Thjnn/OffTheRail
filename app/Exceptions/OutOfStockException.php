<?php

namespace App\Exceptions;

use RuntimeException;

class OutOfStockException extends RuntimeException
{
    /** @param list<string> $names các món không còn đủ hàng, dạng "Tên (Màu / Size)" */
    public function __construct(public readonly array $names)
    {
        parent::__construct('Không đủ hàng: '.implode(', ', $names));
    }
}
