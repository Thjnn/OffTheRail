<?php

namespace App\Providers;

use App\Models\Category;
use App\Support\ShopSettings;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ShopSettings::class);
    }

    public function boot(): void
    {
        // Danh mục cho menu: gắn vào layout, các partial được include sẽ thừa hưởng biến này.
        View::composer('layouts.shop', function ($view) {
            $view->with('navCategories', Category::orderBy('position')->orderBy('id')->get());
        });
    }
}
