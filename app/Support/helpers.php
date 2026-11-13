<?php

use App\Support\ShopSettings;

if (! function_exists('shop')) {
    function shop(string $key, mixed $default = null): mixed
    {
        return app(ShopSettings::class)->get($key, $default);
    }
}
