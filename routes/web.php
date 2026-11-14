<?php

use App\Http\Controllers\Account\AuthController;
use App\Http\Controllers\Account\PasswordResetController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::view('/san-pham', 'products.index', ['category' => null])->name('products.index');
Route::get('/san-pham/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/danh-muc/{category:slug}', fn (Category $category) => view('products.index', ['category' => $category]))
    ->name('categories.show');

Route::view('/gio-hang', 'cart.index')->name('cart');
Route::view('/thanh-toan', 'checkout.index')->name('checkout');
Route::get('/don-hang/{order:code}/thanh-cong', [OrderController::class, 'success'])
    ->middleware('throttle:30,1')->name('orders.success');
Route::get('/tra-cuu-don', [OrderController::class, 'lookupForm'])->name('orders.lookup');
Route::post('/tra-cuu-don', [OrderController::class, 'lookup'])->middleware('throttle:10,1');

Route::prefix('tai-khoan')->group(function () {
    Route::middleware('guest:web')->group(function () {
        Route::get('/dang-ky', [AuthController::class, 'registerForm'])->name('register');
        Route::post('/dang-ky', [AuthController::class, 'register'])->middleware('throttle:10,1');
        Route::get('/dang-nhap', [AuthController::class, 'loginForm'])->name('login');
        Route::post('/dang-nhap', [AuthController::class, 'login']);
        Route::get('/quen-mat-khau', [PasswordResetController::class, 'requestForm'])->name('password.request');
        Route::post('/quen-mat-khau', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
        Route::get('/dat-lai-mat-khau/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
        Route::post('/dat-lai-mat-khau', [PasswordResetController::class, 'reset'])->name('password.update');
    });

    Route::middleware('auth:web')->group(function () {
        Route::post('/dang-xuat', [AuthController::class, 'logout'])->name('logout');
        Route::view('/', 'account.index')->name('account');
    });
});

Route::view('/gioi-thieu', 'pages.about')->name('about');
Route::get('/lien-he', [ContactController::class, 'show'])->name('contact');
Route::post('/lien-he', [ContactController::class, 'store'])->name('contact.store');
