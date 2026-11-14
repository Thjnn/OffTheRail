<?php

use App\Http\Controllers\Admin\AuthController;
use Illuminate\Support\Facades\Route;

// Nạp trong bootstrap/app.php với middleware `web`, prefix `admin`, tên `admin.`.

Route::middleware('guest:admin')->group(function () {
    Route::get('/dang-nhap', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware(['auth:admin', 'admin.active'])->group(function () {
    Route::post('/dang-xuat', [AuthController::class, 'logout'])->name('logout');
    Route::view('/', 'admin.dashboard')->name('dashboard');
});
