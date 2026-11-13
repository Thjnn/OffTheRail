<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Chọn màu + size trên trang chi tiết, hiện giá và tồn kho của biến thể đang chọn.
 * Nút mua chỉ phát event `add-to-cart`; giỏ hàng (Kế hoạch 2) sẽ lắng nghe event này.
 */
class VariantPicker extends Component
{
    public const LOW_STOCK = 2;

    #[Locked]
    public Product $product;

    #[Locked]
    public bool $hasSizeChart = false;

    // Không khai báo kiểu chặt: giá trị do trình duyệt gửi lên, được kiểm tra lại ở updated*().
    public $colorId = null;

    public $size = null;

    public function mount(Product $product, bool $hasSizeChart = false): void
    {
        $this->product = $product->loadMissing('colors', 'variants');
        $this->hasSizeChart = $hasSizeChart;
        $this->colorId = $this->product->colors->first()?->id;
    }

    public function updatedColorId(): void
    {
        $color = is_numeric($this->colorId) ? $this->product->colors->firstWhere('id', (int) $this->colorId) : null;
        $this->colorId = ($color ?? $this->product->colors->first())?->id;

        // Size đang chọn có thể không tồn tại ở màu mới.
        if ($this->size !== null && ! $this->variantsOfColor()->contains('size', $this->size)) {
            $this->size = null;
        }

        $this->dispatch('color-changed', colorId: $this->colorId);
    }

    public function updatedSize(): void
    {
        $this->size = is_string($this->size) && $this->variantsOfColor()->contains('size', $this->size)
            ? $this->size
            : null;
    }

    public function add(bool $buyNow = false): void
    {
        $variant = $this->selectedVariant;

        if ($variant === null || $variant->stock < 1) {
            return;
        }

        $this->dispatch('add-to-cart', variantId: $variant->id, buyNow: $buyNow);
    }

    #[Computed]
    public function selectedVariant(): ?ProductVariant
    {
        return $this->size === null ? null : $this->variantsOfColor()->firstWhere('size', $this->size);
    }

    public function render(): View
    {
        $order = array_flip((array) shop('sizes', []));

        return view('livewire.variant-picker', [
            'variants' => $this->variantsOfColor()
                ->sortBy(fn (ProductVariant $v) => $order[$v->size] ?? PHP_INT_MAX)->values(),
            'variant' => $this->selectedVariant,
            'soldOut' => $this->product->isSoldOut(),
        ]);
    }

    /** @return Collection<int, ProductVariant> */
    private function variantsOfColor(): Collection
    {
        return $this->product->variants->where('product_color_id', $this->colorId)->values();
    }
}
