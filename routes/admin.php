<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderExportController;
use App\Http\Controllers\Admin\StockExportController;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

// Nạp trong bootstrap/app.php với middleware `web`, prefix `admin`, tên `admin.`.

Route::middleware('guest:admin')->group(function () {
    Route::get('/dang-nhap', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware(['auth:admin', 'admin.active'])->group(function () {
    Route::post('/dang-xuat', [AuthController::class, 'logout'])->name('logout');
    Route::view('/', 'admin.dashboard')->name('dashboard');

    Route::view('/don-hang', 'admin.orders.index')->name('orders');
    Route::view('/don-hang/shopee', 'admin.orders.shopee')->name('orders.shopee');
    Route::get('/don-hang/xuat-excel', OrderExportController::class)->middleware('can:owner')->name('orders.export');
    Route::get('/don-hang/{order:code}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/don-hang/{order:code}/trang-thai', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('/don-hang/{order:code}/da-thanh-toan', [OrderController::class, 'markPaid'])->name('orders.paid');
    Route::get('/don-hang/{order:code}/in', [OrderController::class, 'print'])->name('orders.print');

    Route::view('/san-pham', 'admin.products.index')->name('products');
    Route::view('/kho', 'admin.stock.index')->name('stock');
    Route::get('/kho/xuat-excel', StockExportController::class)->middleware('can:owner')->name('stock.export');

    Route::middleware('can:owner')->group(function () {
        Route::get('/san-pham/tao', fn () => view('admin.products.form', ['product' => null]))->name('products.create');
        Route::get('/san-pham/{product:id}/sua', fn (Product $product) => view('admin.products.form', ['product' => $product]))->name('products.edit');

        Route::get('/nhat-ky', ActivityLogController::class)->name('activity');
    });
});
