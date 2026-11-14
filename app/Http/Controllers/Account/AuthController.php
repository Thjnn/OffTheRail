<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\Cart;
use App\Support\Phone;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    public function registerForm(): View
    {
        return view('account.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', $this->phoneRule()],
            'email' => ['nullable', 'email', 'max:190', Rule::unique('customers', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Bạn cho shop xin tên nhé.',
            'phone.required' => 'Bạn nhập số điện thoại nhé.',
            'email.email' => 'Email chưa đúng định dạng.',
            'email.unique' => 'Email này đã được dùng cho tài khoản khác.',
            'password.required' => 'Bạn đặt mật khẩu nhé.',
            'password.min' => 'Mật khẩu tối thiểu 8 ký tự.',
            'password.confirmed' => 'Hai lần nhập mật khẩu chưa khớp.',
        ]);

        $phone = Phone::normalize($data['phone']);
        $customer = Customer::firstWhere('phone', $phone);

        if ($customer?->hasAccount()) {
            throw ValidationException::withMessages(['phone' => 'Số điện thoại này đã có tài khoản. Bạn đăng nhập nhé.']);
        }

        // SĐT từng đặt hàng khi chưa có tài khoản: nhận lại bản ghi đó (lịch sử đơn cũ vẫn chỉ xem qua tra cứu).
        $customer ??= new Customer(['phone' => $phone]);
        $customer->fill([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'password' => $data['password'],
            'registered_at' => now(),
        ])->save();

        $this->startSession($request, $customer);

        return redirect('/tai-khoan');
    }

    public function loginForm(): View
    {
        return view('account.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate(
            ['phone' => ['required', 'string'], 'password' => ['required', 'string']],
            ['phone.required' => 'Bạn nhập số điện thoại nhé.', 'password.required' => 'Bạn nhập mật khẩu nhé.'],
        );

        $phone = Phone::normalize($request->string('phone')) ?? '';
        $key = 'login:'.$phone.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_LOGIN_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            throw ValidationException::withMessages(['phone' => "Đăng nhập sai nhiều lần, thử lại sau {$minutes} phút nhé."]);
        }

        $customer = $phone === '' ? null : Customer::firstWhere('phone', $phone);

        // Bản ghi khách vãng lai (không mật khẩu) không đăng nhập được.
        if (! $customer?->hasAccount() || ! Auth::guard('web')->validate(['phone' => $phone, 'password' => $request->input('password')])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['phone' => 'Số điện thoại hoặc mật khẩu không đúng.']);
        }

        RateLimiter::clear($key);
        $this->startSession($request, $customer, $request->boolean('remember'));

        return redirect()->intended('/tai-khoan');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function startSession(Request $request, Customer $customer, bool $remember = false): void
    {
        Auth::guard('web')->login($customer, $remember);
        $request->session()->regenerate();
        app(Cart::class)->mergeGuestCartInto($customer);
    }

    private function phoneRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (Phone::normalize(is_string($value) ? $value : null) === null) {
                $fail('Số điện thoại gồm 10 số, bắt đầu bằng 0.');
            }
        };
    }
}
