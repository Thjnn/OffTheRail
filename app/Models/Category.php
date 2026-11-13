<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name', 'position', 'size_chart'];

    protected function casts(): array
    {
        return ['size_chart' => 'array'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
