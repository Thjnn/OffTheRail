<?php

namespace App\Livewire\Admin;

use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Support\Inventory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Mọi nhân sự xem được kho; chỉ chủ shop điều chỉnh. */
class StockTable extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public bool $low = false;

    public ?int $adjustingId = null;

    public string $mode = 'import';

    public ?int $qty = null;

    public string $note = '';

    public ?int $historyId = null;

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedLow(): void
    {
        $this->resetPage();
    }

    public function openAdjust(int $variantId): void
    {
        $this->ensureOwner();
        $this->reset('mode', 'qty', 'note');
        $this->resetErrorBag();
        $this->adjustingId = ProductVariant::findOrFail($variantId)->id;
        $this->historyId = null;
    }

    public function adjust(): void
    {
        $this->ensureOwner();

        $this->validate([
            'adjustingId' => ['required', 'integer', 'exists:product_variants,id'],
            'mode' => ['required', 'in:import,count'],
            'qty' => ['required', 'integer', $this->mode === 'import' ? 'min:1' : 'min:0', 'max:100000'],
            'note' => ['required', 'string', 'max:200'],
        ], [], ['qty' => 'số lượng', 'note' => 'lý do']);

        $stock = Inventory::adjust($this->adjustingId, $this->mode, $this->qty, $this->note, auth('admin')->user());

        session()->flash('status', "Đã cập nhật tồn kho: còn {$stock}.");
        $this->adjustingId = null;
    }

    public function showHistory(int $variantId): void
    {
        $this->historyId = $this->historyId === $variantId ? null : $variantId;
        $this->adjustingId = null;
    }

    private function ensureOwner(): void
    {
        abort_unless(auth('admin')->user()?->isOwner(), 403);
    }

    public function render(): View
    {
        $q = trim($this->q);

        return view('livewire.admin.stock-table', [
            'variants' => ProductVariant::with('product', 'color')
                ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('sku', 'like', "%{$q}%")
                    ->orWhere('barcode', $q)
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$q}%"))))
                ->when($this->low, fn ($query) => $query->lowStock())
                ->orderBy('stock')->orderBy('product_id')->orderBy('id')
                ->paginate(30),
            'history' => $this->historyId
                ? StockMovement::with('order:id,code', 'user:id,name')->where('variant_id', $this->historyId)->latest('id')->limit(20)->get()
                : collect(),
            'isOwner' => auth('admin')->user()->isOwner(),
        ]);
    }
}
