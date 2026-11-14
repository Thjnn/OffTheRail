<?php

namespace Tests\Unit;

use App\Support\SizeChart;
use PHPUnit\Framework\TestCase;

class SizeChartTest extends TestCase
{
    public function test_parse_and_format_round_trip(): void
    {
        $chart = SizeChart::parse("Size | Ngực | Dài\nS | 96 | 68\n\nM|100\nL|104|72|thừa\n");

        $this->assertSame(['Size', 'Ngực', 'Dài'], $chart['columns']);
        $this->assertSame([['S', '96', '68'], ['M', '100', ''], ['L', '104', '72']], $chart['rows']);
        $this->assertSame($chart, SizeChart::parse(SizeChart::format($chart)));
    }

    public function test_empty_text_and_null_chart(): void
    {
        $this->assertNull(SizeChart::parse("  \n "));
        $this->assertSame('', SizeChart::format(null));
    }
}
