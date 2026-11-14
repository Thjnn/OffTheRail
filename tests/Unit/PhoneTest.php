<?php

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    public function test_normalizes_common_formats(): void
    {
        foreach (['0901234567', '+84 901 234 567', '84901234567', '0901.234.567', ' 090-123-4567 '] as $raw) {
            $this->assertSame('0901234567', Phone::normalize($raw), $raw);
        }
    }

    public function test_rejects_invalid(): void
    {
        foreach (['12345', '', null, '09012345678', '1901234567', 'abc'] as $raw) {
            $this->assertNull(Phone::normalize($raw));
        }
    }
}
