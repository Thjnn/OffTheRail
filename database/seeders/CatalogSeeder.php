<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $data = require __DIR__.'/data/catalog.php';

        $categories = [];
        foreach ($data['categories'] as $position => $row) {
            $categories[$row['slug']] = Category::updateOrCreate(
                ['slug' => $row['slug']],
                ['name' => $row['name'], 'position' => $position, 'size_chart' => $row['size_chart']],
            );
        }

        foreach ($data['products'] as $row) {
            $product = Product::updateOrCreate(['slug' => $row['slug']], [
                'category_id' => $categories[$row['category']]->id,
                'name' => $row['name'],
                'quote' => $row['quote'],
                'description' => $row['description'],
                'price' => $row['price'],
                'compare_at_price' => $row['compare_at_price'],
                'is_active' => true,
                'is_featured' => $row['is_featured'],
                'published_at' => now()->subDays($row['days_ago']),
            ]);

            $front = $this->image($row['slug'].'-front.webp');
            $alt = $this->image($row['slug'].'-alt.webp');
            $side = $this->image($row['slug'].'-side.webp');

            foreach ($row['colors'] as $index => [$name, $hex]) {
                $color = $product->colors()->updateOrCreate(['position' => $index], ['name' => $name, 'hex' => $hex]);

                $product->images()->updateOrCreate(
                    ['path' => $index === 0 ? $front : $alt],
                    ['product_color_id' => $color->id, 'position' => $index],
                );

                foreach ($row['stock'] as $size => $total) {
                    // Tồn kho cũ tính theo size; chia đều cho các màu, làm tròn lên.
                    $variant = ProductVariant::firstOrNew(['sku' => sprintf('%s-%d-%s', $row['sku'], $index + 1, $size)]);
                    $variant->fill([
                        'product_id' => $product->id,
                        'product_color_id' => $color->id,
                        'size' => $size,
                        'stock' => (int) ceil($total / count($row['colors'])),
                    ]);
                    $variant->barcode ??= 'tmp-'.$variant->sku;
                    $variant->save();
                    $variant->update(['barcode' => $variant->defaultBarcode()]);
                }
            }

            $product->images()->updateOrCreate(['path' => $side], ['product_color_id' => null, 'position' => 2]);

            $product->blocks()->updateOrCreate(['position' => 0], ['image_path' => $side, 'body' => $row['material']]);
            $product->blocks()->updateOrCreate(['position' => 1], ['image_path' => $alt, 'body' => $row['story']]);
        }
    }

    /** Copy ảnh demo vào disk public (bỏ qua nếu đã có) và trả về đường dẫn lưu trong DB. */
    private function image(string $file): string
    {
        $path = 'products/'.$file;
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            $disk->put($path, file_get_contents(__DIR__.'/images/'.$file));
        }

        return $path;
    }
}
