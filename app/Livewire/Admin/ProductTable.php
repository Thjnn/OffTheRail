<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductTable extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $active = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $q = trim($this->q);

        $products = Product::with('category', 'images')
            ->withSum('variants as stock_total', 'stock')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', "%{$q}%")->orWhere('barcode', $q))))
            ->when(ctype_digit($this->category), fn ($query) => $query->where('category_id', (int) $this->category))
            ->when(in_array($this->active, ['1', '0'], true), fn ($query) => $query->where('is_active', $this->active === '1'))
            ->latest('id')
            ->paginate(20);

        return view('livewire.admin.product-table', [
            'products' => $products,
            'categories' => Category::orderBy('position')->get(['id', 'name']),
            'isOwner' => auth('admin')->user()->isOwner(),
        ]);
    }
}
