<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class ShopSettings
{
    private const CACHE_KEY = 'shop.settings';

    private ?array $loaded = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : config('shop.'.$key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;
    }

    private function all(): array
    {
        return $this->loaded ??= Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                return Setting::query()->get()->pluck('value', 'key')->all();
            } catch (\Throwable) {
                return []; // bảng chưa migrate: dùng mặc định
            }
        });
    }
}
