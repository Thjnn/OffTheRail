<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Support\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockExportController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        $rows = (function () {
            foreach (ProductVariant::with('product', 'color')->lazyById(500) as $v) {
                yield [$v->sku, $v->barcode, $v->product->name, $v->color?->name, $v->size, $v->stock, $v->price ?? $v->product->price];
            }
        })();

        return Spreadsheet::download('ton-kho-'.now()->format('Y-m-d').'.xlsx',
            ['SKU', 'Mã vạch', 'Sản phẩm', 'Màu', 'Size', 'Tồn', 'Giá bán'], $rows);
    }
}
