<?php

namespace App\Support;

use App\Models\ProductVariant;

/** Một dòng trong giỏ: biến thể + số lượng, giá luôn lấy từ DB tại thời điểm đọc. */
final readonly class CartLine
{
    public function __construct(public ProductVariant $variant, public int $qty) {}

    public function unitPrice(): int
    {
        return $this->variant->price ?? $this->variant->product->price;
    }

    public function total(): int
    {
        return $this->unitPrice() * $this->qty;
    }

    public function isAvailable(): bool
    {
        return $this->variant->stock >= $this->qty;
    }

    public function imageUrl(): ?string
    {
        return $this->variant->color?->images->first()?->url() ?? $this->variant->product->coverImageUrl();
    }
}
