<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    /** Còn từng này chiếc trở xuống là "sắp hết" (trang sản phẩm cũng hiện "chỉ còn N"). */
    public const LOW_STOCK = 2;

    protected $fillable = ['product_id', 'product_color_id', 'size', 'sku', 'barcode', 'price', 'stock'];

    public function scopeLowStock(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('stock', '<=', self::LOW_STOCK)
            ->whereHas('product', fn ($q) => $q->where('is_active', true));
    }

    protected function casts(): array
    {
        return ['price' => 'integer', 'stock' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class, 'product_color_id');
    }
}
