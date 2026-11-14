<?php

use App\Http\Middleware\EnsureStaffActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$isAdmin = fn (Request $request) => $request->is('admin', 'admin/*');

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) use ($isAdmin): void {
        // Hai hệ đăng nhập tách biệt: khách hàng (guard web) và nhân sự /admin (guard admin).
        $middleware->redirectGuestsTo(fn (Request $request) => $isAdmin($request) ? '/admin/dang-nhap' : '/tai-khoan/dang-nhap');
        $middleware->redirectUsersTo(fn (Request $request) => $isAdmin($request) ? '/admin' : '/tai-khoan');
        $middleware->alias(['admin.active' => EnsureStaffActive::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
