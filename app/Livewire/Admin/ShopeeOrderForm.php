<?php

namespace App\Livewire\Admin;

use App\Exceptions\OrderActionException;
use App\Exceptions\OutOfStockException;
use App\Models\ProductVariant;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class ShopeeOrderForm extends Component
{
    public string $externalCode = '';

    public string $search = '';

    /** @var array<int, int> [variantId => qty] */
    public array $lines = [];

    public ?int $amount = null;

    public function add(int $variantId): void
    {
        if (ProductVariant::whereKey($variantId)->exists()) {
            $this->lines[$variantId] = ($this->lines[$variantId] ?? 0) + 1;
        }
    }

    public function decrease(int $variantId): void
    {
        if (($this->lines[$variantId] ?? 0) > 1) {
            $this->lines[$variantId]--;
        } else {
            unset($this->lines[$variantId]);
        }
    }

    public function remove(int $variantId): void
    {
        unset($this->lines[$variantId]);
    }

    public function save(OrderService $orders)
    {
        $this->validate([
            'externalCode' => ['required', 'string', 'max:40'],
            'amount' => ['required', 'integer', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*' => ['integer', 'min:1'],
        ], [
            'lines.required' => 'Cần chọn ít nhất một món.',
        ], ['externalCode' => 'mã đơn Shopee', 'amount' => 'số tiền thực nhận']);

        try {
            $order = $orders->placeShopeeOrder($this->externalCode, $this->lines, (int) $this->amount, auth('admin')->user());
        } catch (OrderActionException $e) {
            $this->addError('externalCode', $e->getMessage());

            return null;
        } catch (OutOfStockException $e) {
            $this->addError('lines', 'Không đủ hàng: '.implode(', ', $e->names));

            return null;
        }

        session()->flash('status', 'Đã nhập đơn Shopee '.$order->external_code.'.');

        return $this->redirect('/admin/don-hang/'.$order->code);
    }

    public function render(): View
    {
        $selected = ProductVariant::with('product', 'color')->whereKey(array_keys($this->lines))->get()->keyBy('id');

        return view('livewire.admin.shopee-order-form', [
            'results' => $this->results(),
            'selected' => $selected,
            'subtotal' => $selected->sum(fn ($v) => ($v->price ?? $v->product->price) * ($this->lines[$v->id] ?? 0)),
        ]);
    }

    private function results(): Collection
    {
        $q = trim($this->search);

        if (mb_strlen($q) < 2) {
            return new Collection;
        }

        return ProductVariant::with('product', 'color')
            ->where(fn ($w) => $w->where('sku', 'like', "%{$q}%")
                ->orWhere('barcode', $q)
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$q}%")))
            ->orderBy('product_id')->limit(8)->get();
    }
}
