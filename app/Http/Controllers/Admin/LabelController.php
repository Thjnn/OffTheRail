<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** In tem mã vạch (Code128 của cột barcode — thứ máy quét ở quầy đọc). */
class LabelController extends Controller
{
    private const MAX_LABELS = 2000;

    public function __invoke(Request $request, Product $product): View
    {
        $variants = $product->variants()->with('color')->orderBy('product_color_id')->orderBy('id')->get();

        if (! $request->has('copies')) {
            return view('admin.products.labels', ['product' => $product, 'variants' => $variants]);
        }

        $request->validate(['copies' => ['array'], 'copies.*' => ['integer', 'min:0']], [], ['copies.*' => 'số tem']);

        // Chỉ tính biến thể của chính sản phẩm này; kiểm tổng trước khi dựng danh sách tem.
        $copies = collect((array) $request->input('copies'))->map(fn ($n) => (int) $n)->only($variants->modelKeys());

        if ($copies->sum() > self::MAX_LABELS) {
            throw ValidationException::withMessages(['copies' => 'Mỗi lần in tối đa '.self::MAX_LABELS.' tem.']);
        }

        $labels = $variants->flatMap(fn ($v) => array_fill(0, $copies->get($v->id, 0), $v));

        return view('admin.products.labels-print', ['product' => $product, 'labels' => $labels]);
    }
}
