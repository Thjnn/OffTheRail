<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('account.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => ['required', 'email']],
            ['email.required' => 'Bạn nhập email nhé.', 'email.email' => 'Email chưa đúng định dạng.'],
        );

        Password::broker('customers')->sendResetLink($request->only('email'));

        // Cùng một câu cho mọi trường hợp, để không dò được email nào có tài khoản.
        return back()->with('status', 'Nếu email này có tài khoản, shop đã gửi link đặt lại mật khẩu.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('account.reset-password', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'Mật khẩu tối thiểu 8 ký tự.',
            'password.confirmed' => 'Hai lần nhập mật khẩu chưa khớp.',
        ]);

        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password) {
                $customer->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($customer));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Bạn yêu cầu link mới nhé.']);
        }

        return redirect('/tai-khoan/dang-nhap')->with('status', 'Đã đặt lại mật khẩu. Bạn đăng nhập bằng mật khẩu mới nhé.');
    }
}
