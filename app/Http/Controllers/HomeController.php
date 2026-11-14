<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Banner;
use App\Models\OrderItem;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $sections = HomeSection::where('is_active', true)
            ->whereIn('type', HomeSection::TYPES)
            ->orderBy('position')->orderBy('id')
            ->get()
            ->map(fn (HomeSection $section) => ['section' => $section, 'items' => $this->itemsFor($section)])
            // Khối sản phẩm/danh mục rỗng thì không hiện tiêu đề trơ trọi.
            ->filter(fn (array $row) => $row['section']->type === 'story' || $row['items']->isNotEmpty())
            ->values();

        return view('home', [
            'banners' => Banner::where('is_active', true)->orderBy('position')->orderBy('id')->get(),
            'sections' => $sections,
        ]);
    }

    /** Bán chạy 60 ngày qua (bỏ đơn huỷ/hoàn); chưa đủ thì bù bằng sản phẩm nổi bật, rồi hàng mới. */
    private function bestSellers(int $limit): Collection
    {
        $sold = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.variant_id')
            ->whereNotIn('orders.status', [OrderStatus::Cancelled->value, OrderStatus::Returned->value])
            ->where('orders.created_at', '>=', now()->subDays(60))
            ->groupBy('product_variants.product_id')
            ->orderByRaw('SUM(order_items.qty) DESC')
            ->pluck('product_variants.product_id');

        $ranked = Product::visible()->with('images', 'variants')->whereKey($sold)->get()
            ->sortBy(fn (Product $p) => $sold->search($p->id))->values()->take($limit);

        if ($ranked->count() < $limit) {
            $ranked = $ranked->concat(Product::visible()->with('images', 'variants')
                ->whereKeyNot($ranked->modelKeys())
                ->orderByDesc('is_featured')->orderByDesc('published_at')
                ->limit($limit - $ranked->count())->get());
        }

        return $ranked;
    }

    private function itemsFor(HomeSection $section): Collection
    {
        $limit = max(1, min(24, (int) $section->option('limit', 8)));

        return match ($section->type) {
            'new_arrivals' => Product::visible()->with('images', 'variants')
                ->orderByDesc('published_at')->orderByDesc('id')->limit($limit)->get(),
            'featured' => $this->bestSellers($limit),
            'categories' => Category::whereHas('products', fn ($q) => $q->visible())
                ->with(['products' => fn ($q) => $q->visible()->with('images')->orderByDesc('published_at')])
                ->orderBy('position')->orderBy('id')->get(),
            default => collect(),
        };
    }
}
