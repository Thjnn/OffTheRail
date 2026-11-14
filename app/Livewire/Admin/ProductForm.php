<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\OwnerOnly;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProductForm extends Component
{
    use OwnerOnly;

    public ?int $productId = null;

    public string $name = '';

    public string $slug = '';

    public ?int $category_id = null;

    public string $quote = '';

    public string $description = '';

    public ?int $price = null;

    public ?int $compare_at_price = null;

    public bool $is_active = true;

    public bool $is_featured = false;

    public string $published_at = '';

    /** @var list<array{id: ?int, name: string, hex: string}> */
    public array $colors = [];

    /** @var list<array{id: ?int, color: int, size: string, sku: ?string, barcode: ?string, price: ?int, stock: ?int, current?: int}> */
    public array $variants = [];

    public function mount(?Product $product = null): void
    {
        if ($product === null || ! $product->exists) {
            $this->published_at = now()->format('Y-m-d\TH:i');

            return;
        }

        $this->productId = $product->id;
        $this->fill($product->only(['name', 'slug', 'category_id', 'price', 'compare_at_price', 'is_active', 'is_featured']));
        $this->quote = (string) $product->quote;
        $this->description = (string) $product->description;
        $this->published_at = $product->published_at?->format('Y-m-d\TH:i') ?? '';

        $colors = $product->colors()->get();
        $index = $colors->pluck('id')->flip();
        $this->colors = $colors->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'hex' => $c->hex])->all();
        $this->variants = $product->variants()->orderBy('id')->get()->map(fn ($v) => [
            'id' => $v->id, 'color' => $index[$v->product_color_id] ?? 0, 'size' => $v->size,
            'sku' => $v->sku, 'barcode' => $v->barcode, 'price' => $v->price, 'stock' => null, 'current' => $v->stock,
        ])->all();
    }

    public function addColor(): void
    {
        $this->colors[] = ['id' => null, 'name' => '', 'hex' => '#1b1b1f'];
    }

    public function removeColor(int $index): void
    {
        unset($this->colors[$index]);
        $this->colors = array_values($this->colors);

        // Bỏ biến thể của màu này, dồn chỉ số màu phía sau.
        $this->variants = array_values(array_filter(array_map(function (array $v) use ($index) {
            if ($v['color'] === $index) {
                return null;
            }
            $v['color'] -= $v['color'] > $index ? 1 : 0;

            return $v;
        }, $this->variants)));
    }

    /** Bật/tắt ô màu × size trong ma trận. */
    public function toggleCell(int $color, string $size): void
    {
        foreach ($this->variants as $i => $v) {
            if ($v['color'] === $color && $v['size'] === $size) {
                unset($this->variants[$i]);
                $this->variants = array_values($this->variants);

                return;
            }
        }

        if (isset($this->colors[$color])) {
            $this->variants[] = ['id' => null, 'color' => $color, 'size' => $size, 'sku' => null, 'barcode' => null, 'price' => null, 'stock' => 0];
        }
    }

    public function save(ProductService $products)
    {
        if (trim($this->slug) === '') {
            $this->slug = Str::slug($this->name);
        }

        foreach ($this->variants as $i => $v) {
            foreach (['sku', 'barcode'] as $key) {
                $this->variants[$i][$key] = trim((string) ($v[$key] ?? '')) ?: null;
            }
            foreach (['price', 'stock'] as $key) {
                $this->variants[$i][$key] = ($v[$key] ?? '') === '' ? null : $v[$key];
            }
        }

        $data = $this->validate($this->rules(), [], $this->attributes());
        $data['quote'] = $data['quote'] ?: null;
        $data['description'] = $data['description'] ?: null;
        $data['published_at'] = $data['published_at'] ?: null;
        $data['variants'] = array_map(fn ($v) => $v['id'] ? array_merge($v, ['stock' => null]) : $v, $data['variants']);

        $product = $products->save($this->productId ? Product::findOrFail($this->productId) : null, $data, auth('admin')->user());

        session()->flash('status', 'Đã lưu sản phẩm.');

        return $this->redirect('/admin/san-pham/'.$product->id.'/sua');
    }

    public function delete(ProductService $products)
    {
        $products->delete(Product::findOrFail($this->productId));
        session()->flash('status', 'Đã xoá sản phẩm.');

        return $this->redirect('/admin/san-pham');
    }

    protected function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('products', 'slug')->ignore($this->productId)],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'quote' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:0'],
            'compare_at_price' => ['nullable', 'integer', 'gt:price'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'colors' => ['array', 'max:20'],
            'colors.*.id' => ['nullable', 'integer'],
            'colors.*.name' => ['required', 'string', 'max:40'],
            'colors.*.hex' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'variants' => ['array'],
        ];

        // Trùng giữa các dòng trong chính form (unique chỉ so với DB).
        $notRepeated = fn (string $key, int $i) => function ($attribute, $value, $fail) use ($key, $i) {
            foreach ($this->variants as $j => $other) {
                if ($j !== $i && ($other[$key] ?? null) === $value) {
                    $fail(($key === 'sku' ? 'SKU' : 'Mã vạch').' bị trùng giữa các biến thể.');

                    return;
                }
            }
        };

        foreach ($this->variants as $i => $v) {
            $rules["variants.$i.id"] = ['nullable', 'integer'];
            $rules["variants.$i.color"] = ['required', 'integer', 'min:0', 'max:'.max(0, count($this->colors) - 1)];
            $rules["variants.$i.size"] = ['required', Rule::in(shop('sizes'))];
            $rules["variants.$i.sku"] = ['nullable', 'string', 'max:64', $notRepeated('sku', $i), Rule::unique('product_variants', 'sku')->ignore($v['id'] ?? null)];
            $rules["variants.$i.barcode"] = ['nullable', 'string', 'max:64', $notRepeated('barcode', $i), Rule::unique('product_variants', 'barcode')->ignore($v['id'] ?? null)];
            $rules["variants.$i.price"] = ['nullable', 'integer', 'min:0'];
            $rules["variants.$i.stock"] = ['nullable', 'integer', 'min:0', 'max:100000'];
        }

        return $rules;
    }

    protected function attributes(): array
    {
        return [
            'compare_at_price' => 'giá gốc', 'quote' => 'câu trích', 'description' => 'mô tả', 'published_at' => 'ngày đăng',
            'colors.*.name' => 'tên màu', 'colors.*.hex' => 'mã màu', 'variants.*.size' => 'size',
            'variants.*.sku' => 'SKU', 'variants.*.barcode' => 'mã vạch', 'variants.*.price' => 'giá riêng',
            'variants.*.stock' => 'tồn ban đầu', 'variants.*.color' => 'màu',
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.product-form', [
            'categories' => Category::orderBy('position')->orderBy('id')->get(['id', 'name']),
            'sizes' => shop('sizes'),
        ]);
    }
}
