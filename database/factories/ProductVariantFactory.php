<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_color_id' => ProductColor::factory(),
            'size' => 'M',
            'sku' => Str::upper(Str::random(8)),
            'barcode' => fake()->unique()->numerify('############'),
            'stock' => 5,
        ];
    }
}
