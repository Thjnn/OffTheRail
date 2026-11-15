<?php

namespace App\Livewire\Admin;

use App\Exceptions\OrderActionException;
use App\Exceptions\OutOfStockException;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Services\OrderService;
use App\Services\ShiftService;
use App\Support\Phone;
use App\Support\VietQr;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

/**
 * Màn bán tại quầy. Giỏ hàng nằm ở Alpine (danh sách biến thể nạp sẵn) để quét liên tục không chờ server;
 * server chỉ được gọi khi mã không có trong danh sách và khi thanh toán (tính lại toàn bộ tiền).
 */
class Pos extends Component
{
    public ?int $openingCash = null;

    /** Màn bán đã hiện thì giữ nguyên dù ca bị chốt ở tab khác — để không mất hoá đơn đang lập trong Alpine. */
    public bool $registerShown = false;

    public function openShift(ShiftService $shifts): void
    {
        $this->validate(['openingCash' => ['required', 'integer', 'min:0']], [], ['openingCash' => 'tiền đầu ca']);

        try {
            $shifts->open($this->openingCash, auth('admin')->user());
        } catch (OrderActionException $e) {
            $this->addError('openingCash', $e->getMessage());
        }
    }

    /** @return array{variants: list<array<string, mixed>>, categories: list<array{id: int, name: string}>} */
    public function catalog(): array
    {
        $variants = ProductVariant::with(['product.images', 'color'])->get()
            ->sortBy(fn (ProductVariant $v) => [$v->product->name, $v->color?->position, $v->id])
            ->map(fn (ProductVariant $v) => $this->row($v))
            ->values()->all();

        return [
            'variants' => $variants,
            'categories' => Category::orderBy('position')->get(['id', 'name'])->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->all(),
        ];
    }

    /** Mã quét không có trong danh sách đã nạp (ví dụ sản phẩm vừa tạo): tìm theo mã vạch hoặc SKU. */
    public function lookup(string $code): ?array
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $variant = ProductVariant::with(['product.images', 'color'])
            ->where('barcode', $code)
            ->orWhereRaw('lower(sku) = ?', [mb_strtolower($code)])
            ->first();

        return $variant ? $this->row($variant) : null;
    }

    /** @return array{name: string, orders_count: int}|null */
    public function findCustomer(string $phone): ?array
    {
        $normalized = Phone::normalize($phone);
        $customer = $normalized ? Customer::where('phone', $normalized)->first() : null;

        return $customer ? ['name' => $customer->name, 'orders_count' => (int) $customer->orders_count] : null;
    }

    public function qr(int $amount): ?string
    {
        return VietQr::image(max(0, $amount), 'POS '.now()->format('dmHi'));
    }

    /** @param  list<array{id: int, qty: int}>  $lines */
    public function checkout(array $lines, int $discount, string $method, ?int $cashReceived, ?string $phone, ?string $name, string $token, OrderService $orders)
    {
        $items = [];

        foreach ($lines as $line) {
            $id = (int) ($line['id'] ?? 0);
            $items[$id] = ($items[$id] ?? 0) + (int) ($line['qty'] ?? 0);
        }

        // Bấm "Thanh toán" hai lần: lần sau cùng token bị bỏ qua.
        $key = 'pos-checkout:'.$token;

        if (! Cache::add($key, true, 600)) {
            return null;
        }

        try {
            $order = $orders->placePosOrder($items, $discount, $method, $cashReceived, $phone, $name, auth('admin')->user());
        } catch (OrderActionException $e) {
            Cache::forget($key);
            $this->addError('checkout', $e->getMessage());

            return null;
        } catch (OutOfStockException $e) {
            Cache::forget($key);
            $this->addError('checkout', 'Không đủ hàng: '.implode(', ', $e->names));

            return null;
        }

        return $this->redirect('/admin/don-hang/'.$order->code.'/hoa-don');
    }

    public function render(ShiftService $shifts): View
    {
        $shift = $shifts->current();
        $user = auth('admin')->user();
        $this->registerShown = $this->registerShown || $shift !== null;

        return view('livewire.admin.pos', [
            'shift' => $shift,
            'catalog' => $this->registerShown ? $this->catalog() : null,
            'maxDiscountPercent' => $user->isOwner() ? null : (int) shop('staff_max_discount_percent'),
            'qrReady' => VietQr::isConfigured(),
        ]);
    }

    /** @return array<string, mixed> */
    private function row(ProductVariant $v): array
    {
        return [
            'id' => $v->id,
            'product_id' => $v->product_id,
            'category_id' => $v->product->category_id,
            'name' => $v->product->name,
            'color' => $v->color?->name,
            'hex' => $v->color?->hex,
            'size' => $v->size,
            'sku' => $v->sku,
            'barcode' => $v->barcode,
            'price' => $v->price ?? $v->product->price,
            'stock' => $v->stock,
            'image' => $v->product->coverImageUrl(),
        ];
    }
}
