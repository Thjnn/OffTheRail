<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_formats_vnd(): void
    {
        $this->assertSame('350.000đ', Money::vnd(350000));
        $this->assertSame('0đ', Money::vnd(0));
        $this->assertSame('1.250.000đ', Money::vnd(1250000));
    }
}
