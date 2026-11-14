<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Nhân sự bị khoá trong lúc đang đăng nhập: phiên chết ngay ở request kế tiếp. */
class EnsureStaffActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user();

        if ($user !== null && ! $user->is_active) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/admin/dang-nhap')->withErrors(['email' => 'Tài khoản đã bị khoá.']);
        }

        return $next($request);
    }
}
