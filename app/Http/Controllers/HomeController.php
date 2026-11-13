<?php

namespace App\Http\Controllers;

use App\Models\Banner;
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

    private function itemsFor(HomeSection $section): Collection
    {
        $limit = max(1, min(24, (int) $section->option('limit', 8)));

        return match ($section->type) {
            'new_arrivals' => Product::visible()->with('images', 'variants')
                ->orderByDesc('published_at')->orderByDesc('id')->limit($limit)->get(),
            'featured' => Product::visible()->with('images', 'variants')
                ->where('is_featured', true)->orderByDesc('published_at')->limit($limit)->get(),
            'categories' => Category::whereHas('products', fn ($q) => $q->visible())
                ->with(['products' => fn ($q) => $q->visible()->with('images')->orderByDesc('published_at')])
                ->orderBy('position')->orderBy('id')->get(),
            default => collect(),
        };
    }
}
