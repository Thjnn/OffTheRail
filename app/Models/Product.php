<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    public const NEW_DAYS = 30;

    protected $fillable = [
        'category_id', 'slug', 'name', 'quote', 'description', 'price',
        'compare_at_price', 'is_active', 'is_featured', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function colors(): HasMany
    {
        return $this->hasMany(ProductColor::class)->orderBy('position')->orderBy('id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(ProductBlock::class)->orderBy('position')->orderBy('id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function isVisible(): bool
    {
        return $this->is_active && ($this->published_at === null || $this->published_at->lte(now()));
    }

    public function totalStock(): int
    {
        return (int) $this->variants->sum('stock');
    }

    public function isSoldOut(): bool
    {
        return $this->totalStock() === 0;
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->price;
    }

    public function discountPercent(): ?int
    {
        return $this->isOnSale()
            ? (int) round(($this->compare_at_price - $this->price) / $this->compare_at_price * 100)
            : null;
    }

    public function isNew(): bool
    {
        return $this->published_at !== null
            && $this->published_at->lte(now())
            && $this->published_at->gte(now()->subDays(self::NEW_DAYS));
    }

    public function coverImageUrl(): ?string
    {
        return $this->images->first()?->url();
    }
}
