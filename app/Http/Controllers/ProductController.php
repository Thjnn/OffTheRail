<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        // Sản phẩm ẩn hoặc chưa tới ngày mở bán: coi như không tồn tại.
        abort_unless($product->isVisible(), 404);

        $product->load('category', 'colors', 'images', 'blocks', 'variants');

        $related = Product::visible()->with('images', 'variants')
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->orderByDesc('published_at')->limit(4)->get();

        // Ảnh đại diện cho từng màu, để đổi ảnh đầu trang khi khách chọn màu.
        $colorImages = $product->images->whereNotNull('product_color_id')
            ->unique('product_color_id')
            ->mapWithKeys(fn ($image) => [$image->product_color_id => $image->url()]);

        return view('products.show', [
            'product' => $product,
            'related' => $related,
            'colorImages' => $colorImages,
            'cover' => $product->coverImageUrl(),
            'ogDescription' => Money::vnd($product->price).' · '
                .($product->quote ?: Str::limit((string) $product->description, 120)),
        ]);
    }
}
