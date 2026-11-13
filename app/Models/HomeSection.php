<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    /** Các loại khối trang chủ mà giao diện biết cách hiển thị. */
    public const TYPES = ['new_arrivals', 'categories', 'featured', 'story'];

    protected $fillable = ['type', 'title', 'config', 'position', 'is_active'];

    protected function casts(): array
    {
        return ['config' => 'array', 'is_active' => 'boolean'];
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}
