<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductList extends Component
{
    use WithPagination;

    public const SORTS = [
        'moi-nhat' => 'Mới nhất',
        'gia-tang' => 'Giá thấp → cao',
        'gia-giam' => 'Giá cao → thấp',
        'ten' => 'Tên A–Z',
    ];

    public const PER_PAGE = 12;

    /*
     * Bộ lọc đồng bộ lên URL. Cố ý không khai báo kiểu: giá trị đến từ thanh địa chỉ
     * hoặc trình duyệt nên có thể là bất cứ thứ gì; clean() đưa về giá trị hợp lệ.
     */
    #[Url]
    public $danhMuc = '';

    #[Url]
    public $size = '';

    #[Url]
    public $mau = '';

    #[Url]
    public $giaTu = null;

    #[Url]
    public $giaDen = null;

    #[Url]
    public $sapXep = 'moi-nhat';

    #[Url]
    public $q = '';

    public function mount(?string $category = null): void
    {
        if ($category !== null) {
            $this->danhMuc = $category;
        }

        $this->clean();
    }

    public function updated(): void
    {
        $this->clean();
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('danhMuc', 'size', 'mau', 'giaTu', 'giaDen', 'q');
        $this->resetPage();
    }

    public function render(): View
    {
        $this->clean();

        return view('livewire.product-list', [
            'products' => $this->query()->paginate(self::PER_PAGE),
            'categories' => Category::orderBy('position')->orderBy('id')->get(),
            'sizes' => (array) shop('sizes', []),
            'colors' => ProductColor::whereHas('product', fn (Builder $q) => $q->visible())
                ->orderBy('name')->get(['name', 'hex'])->unique('name')->values(),
            'hasFilters' => $this->danhMuc !== '' || $this->size !== '' || $this->mau !== ''
                || $this->giaTu !== null || $this->giaDen !== null || $this->q !== '',
        ]);
    }

    private function query(): Builder
    {
        $like = fn (string $value) => '%'.addcslashes($value, '%_\\').'%';

        return Product::visible()->with('images', 'variants')
            ->when($this->danhMuc !== '', fn (Builder $q) => $q->whereHas('category', fn (Builder $c) => $c->where('slug', $this->danhMuc)))
            ->when($this->size !== '', fn (Builder $q) => $q->whereHas('variants', fn (Builder $v) => $v->where('size', $this->size)->where('stock', '>', 0)))
            ->when($this->mau !== '', fn (Builder $q) => $q->whereHas('colors', fn (Builder $c) => $c->where('name', 'like', $like($this->mau))))
            ->when($this->giaTu !== null, fn (Builder $q) => $q->where('price', '>=', $this->giaTu))
            ->when($this->giaDen !== null, fn (Builder $q) => $q->where('price', '<=', $this->giaDen))
            ->when($this->q !== '', fn (Builder $q) => $q->where('name', 'like', $like($this->q)))
            ->when(true, fn (Builder $q) => match ($this->sapXep) {
                'gia-tang' => $q->orderBy('price'),
                'gia-giam' => $q->orderByDesc('price'),
                'ten' => $q->orderBy('name'),
                default => $q->orderByDesc('published_at'),
            })
            ->orderByDesc('id');
    }

    /** Đưa mọi bộ lọc về giá trị hợp lệ; thứ gì lạ thì coi như không lọc. */
    private function clean(): void
    {
        $text = fn ($value) => is_string($value) ? mb_substr(trim($value), 0, 60) : '';
        $price = fn ($value) => is_numeric($value) && (int) $value > 0 ? min((int) $value, 1_000_000_000) : null;

        $this->danhMuc = $text($this->danhMuc);
        $this->mau = $text($this->mau);
        $this->q = $text($this->q);
        $this->size = in_array($this->size, (array) shop('sizes', []), true) ? $this->size : '';
        $this->sapXep = is_string($this->sapXep) && isset(self::SORTS[$this->sapXep]) ? $this->sapXep : 'moi-nhat';
        $this->giaTu = $price($this->giaTu);
        $this->giaDen = $price($this->giaDen);
    }
}
