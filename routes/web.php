<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::view('/san-pham', 'products.index', ['category' => null])->name('products.index');
Route::get('/san-pham/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/danh-muc/{category:slug}', fn (Category $category) => view('products.index', ['category' => $category]))
    ->name('categories.show');

Route::view('/gio-hang', 'cart.index')->name('cart');

Route::view('/gioi-thieu', 'pages.about')->name('about');
Route::get('/lien-he', [ContactController::class, 'show'])->name('contact');
Route::post('/lien-he', [ContactController::class, 'store'])->name('contact.store');
