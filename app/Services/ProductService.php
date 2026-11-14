<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductService
{
    private const PRODUCT_FIELDS = [
        'name', 'slug', 'category_id', 'quote', 'description', 'price',
        'compare_at_price', 'is_active', 'is_featured', 'published_at',
    ];

    /**
     * Lưu sản phẩm cùng màu và ma trận biến thể. $data đã validate.
     *
     * @param  array{colors: list<array{id?: ?int, name: string, hex: string}>, variants: list<array{id?: ?int, color: int, size: string, sku?: ?string, barcode?: ?string, price?: ?int, stock?: ?int}>}  $data
     *
     * @throws ValidationException khi xoá biến thể đã có đơn
     */
    public function save(?Product $product, array $data, User $by): Product
    {
        return DB::transaction(function () use ($product, $data, $by) {
            $product ??= new Product;
            $oldPrice = $product->exists ? $product->price : null;

            $product->fill(array_intersect_key($data, array_flip(self::PRODUCT_FIELDS)))->save();

            if ($oldPrice !== null && $oldPrice !== $product->price) {
                ActivityLog::record('price.update', $product, ['price' => $oldPrice], ['price' => $product->price]);
            }

            // Màu: giữ theo id, tạo mới cái chưa có. $colorIds[index trong form] = id thật.
            $keptColorIds = [];
            $colorIds = [];
            foreach (array_values($data['colors']) as $position => $c) {
                $color = $product->colors()->updateOrCreate(['id' => $c['id'] ?? null], [
                    'name' => $c['name'], 'hex' => $c['hex'], 'position' => $position,
                ]);
                $colorIds[$position] = $keptColorIds[] = $color->id;
            }

            $keptVariantIds = array_values(array_filter(array_column($data['variants'], 'id')));
            $removed = $product->variants()->whereNotIn('id', $keptVariantIds)->get();
            $withOrders = $removed->filter(fn (ProductVariant $v) => OrderItem::where('variant_id', $v->id)->exists());

            if ($withOrders->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'variants' => 'Biến thể '.$withOrders->pluck('sku')->implode(', ').' đã có đơn hàng, không xoá được. Hãy đặt tồn kho về 0.',
                ]);
            }

            $product->variants()->whereKey($removed->modelKeys())->delete();
            $product->colors()->whereNotIn('id', $keptColorIds)->delete();

            foreach ($data['variants'] as $row) {
                $colorId = $colorIds[$row['color']];
                $fields = ['product_color_id' => $colorId, 'size' => $row['size'], 'price' => $row['price'] ?? null];

                if (! empty($row['id'])) {
                    $variant = $product->variants()->findOrFail($row['id']);
                    $oldVariantPrice = $variant->price;
                    $variant->update($fields + array_filter([
                        'sku' => $row['sku'] ?? null, 'barcode' => $row['barcode'] ?? null,
                    ]));

                    if ($oldVariantPrice !== $variant->price) {
                        ActivityLog::record('price.update', $variant, ['price' => $oldVariantPrice], ['price' => $variant->price]);
                    }

                    continue;
                }

                $tmp = 'tmp-'.Str::random(12);
                $variant = $product->variants()->create($fields + [
                    'sku' => $row['sku'] ?? $tmp, 'barcode' => $row['barcode'] ?? $tmp, 'stock' => 0,
                ]);
                $variant->update([
                    'sku' => $row['sku'] ?? sprintf('SP%04d-%d-%s', $product->id, $row['color'] + 1, $row['size']),
                    'barcode' => $row['barcode'] ?? $variant->defaultBarcode(),
                ]);

                if (($row['stock'] ?? 0) > 0) {
                    Inventory::putBack($variant->id, (int) $row['stock'], 'import', null, $by->id, 'Tồn ban đầu');
                }
            }

            return $product->refresh();
        });
    }

    /** @throws ValidationException khi sản phẩm đã có đơn */
    public function delete(Product $product): void
    {
        if (OrderItem::whereIn('variant_id', $product->variants()->select('id'))->exists()) {
            throw ValidationException::withMessages(['product' => 'Sản phẩm đã có đơn hàng, hãy ẩn thay vì xoá.']);
        }

        $files = $product->images()->pluck('path')->merge($product->blocks()->pluck('image_path'))->filter()->all();
        $product->delete();
        Storage::disk('public')->delete($files);
    }
}
