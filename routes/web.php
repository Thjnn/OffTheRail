<?php

use App\Http\Controllers\HomeController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::view('/san-pham', 'products.index', ['category' => null])->name('products.index');
Route::get('/danh-muc/{category:slug}', fn (Category $category) => view('products.index', ['category' => $category]))
    ->name('categories.show');
