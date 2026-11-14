<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class OrderExportTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<list<mixed>> */
    public static function readXlsx(string $content): array
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $content);
        $reader = new Reader;
        $reader->open($path);
        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }

        $reader->close();
        unlink($path);

        return $rows;
    }

    public function test_owner_downloads_filtered_orders(): void
    {
        $this->placeOrder();
        $confirmed = $this->placeOrder(['phone' => '0911111111']);
        app(OrderService::class)->transition($confirmed, OrderStatus::Confirmed, User::factory()->create());

        $response = $this->actingAs(User::factory()->owner()->create(), 'admin')->get('/admin/don-hang/xuat-excel?status=confirmed');

        $response->assertOk()->assertDownload('don-hang-'.now()->format('Y-m-d').'.xlsx');
        $rows = self::readXlsx($response->streamedContent());
        $this->assertCount(2, $rows);
        $this->assertSame('Mã đơn', $rows[0][0]);
        $this->assertSame($confirmed->code, $rows[1][0]);
        $this->assertSame('Đã xác nhận', $rows[1][6]);
        $this->assertSame($confirmed->total, $rows[1][12]);
    }

    public function test_staff_cannot_export(): void
    {
        $this->actingAs(User::factory()->create(), 'admin')->get('/admin/don-hang/xuat-excel')->assertForbidden();
    }
}
