<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Giỏ hàng dạng [variantId => qty]. Khách vãng lai lưu trong session,
 * khách đã đăng nhập lưu ở cột customers.cart (để giỏ đi theo tài khoản).
 */
class Cart
{
    public const MAX_QTY = 99;

    private const SESSION_KEY = 'cart';

    public function add(int $variantId, int $qty = 1): bool
    {
        $variant = ProductVariant::with('product')->find($variantId);

        if ($variant === null || $variant->stock < 1 || ! $variant->product?->isVisible()) {
            return false;
        }

        $items = $this->items();
        $items[$variantId] = $this->clamp(($items[$variantId] ?? 0) + $qty, $variant->stock);
        $this->store($items);

        return true;
    }

    public function setQty(int $variantId, int $qty): void
    {
        $items = $this->items();

        if (! isset($items[$variantId])) {
            return;
        }

        $stock = ProductVariant::whereKey($variantId)->value('stock');

        if ($qty <= 0 || $stock === null) {
            unset($items[$variantId]);
        } else {
            // Kho đã về 0 thì giữ dòng ở số lượng 1 để giỏ báo "Hết hàng" thay vì lặng lẽ biến mất.
            $items[$variantId] = $this->clamp($qty, max(1, (int) $stock));
        }

        $this->store($items);
    }

    public function remove(int $variantId): void
    {
        $this->setQty($variantId, 0);
    }

    public function clear(): void
    {
        $this->store([]);
    }

    /** @return array<int, int> */
    public function items(): array
    {
        $raw = $this->customer()?->cart ?? session(self::SESSION_KEY, []);
        $items = [];

        foreach (is_array($raw) ? $raw : [] as $id => $qty) {
            if (is_numeric($id) && is_numeric($qty) && $qty > 0) {
                $items[(int) $id] = min((int) $qty, self::MAX_QTY);
            }
        }

        return $items;
    }

    /** @return Collection<int, CartLine> */
    public function lines(): Collection
    {
        $items = $this->items();

        if ($items === []) {
            return collect();
        }

        $variants = ProductVariant::with('product.images', 'color.images')
            ->whereKey(array_keys($items))->get()
            ->filter(fn (ProductVariant $v) => $v->product?->isVisible())
            ->keyBy('id');

        // Món đã bị xoá hoặc ẩn khỏi shop: bỏ luôn khỏi giỏ.
        if ($variants->count() !== count($items)) {
            $items = array_intersect_key($items, $variants->all());
            $this->store($items);
        }

        return collect($items)->map(fn (int $qty, int $id) => new CartLine($variants[$id], $qty))->values();
    }

    public function count(): int
    {
        return array_sum($this->items());
    }

    public function subtotal(): int
    {
        return (int) $this->lines()->sum(fn (CartLine $line) => $line->total());
    }

    public function isEmpty(): bool
    {
        return $this->items() === [];
    }

    public function hasUnavailable(): bool
    {
        return $this->lines()->contains(fn (CartLine $line) => ! $line->isAvailable());
    }

    public function mergeGuestCartInto(Customer $customer): void
    {
        $merged = is_array($customer->cart) ? $customer->cart : [];

        foreach (session()->pull(self::SESSION_KEY, []) as $id => $qty) {
            $merged[$id] = min(($merged[$id] ?? 0) + (int) $qty, self::MAX_QTY);
        }

        $customer->update(['cart' => $merged]);
    }

    private function clamp(int $qty, int $stock): int
    {
        return max(1, min($qty, $stock, self::MAX_QTY));
    }

    private function customer(): ?Customer
    {
        return auth('web')->user();
    }

    private function store(array $items): void
    {
        if ($customer = $this->customer()) {
            $customer->update(['cart' => $items]);
        } else {
            session([self::SESSION_KEY => $items]);
        }
    }
}
