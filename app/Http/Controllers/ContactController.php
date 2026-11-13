<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ContactController extends Controller
{
    private const MAX_PER_HOUR = 5;

    private const THANKS = 'Cảm ơn bạn! Shop đã nhận được lời nhắn và sẽ liên hệ lại sớm.';

    public function show(): View
    {
        return view('pages.contact');
    }

    public function store(Request $request): RedirectResponse
    {
        // Ô bẫy: người thật không thấy ô này. Bot điền vào thì bỏ qua nhưng vẫn báo thành công.
        if ($request->filled('website')) {
            return redirect('/lien-he')->with('status', self::THANKS);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'required_without:email', 'regex:/^0\d{9}$/'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:190'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'name.required' => 'Bạn cho shop xin tên nhé.',
            'name.max' => 'Tên hơi dài, tối đa 120 ký tự.',
            'phone.required_without' => 'Nhập số điện thoại hoặc email để shop liên hệ lại.',
            'phone.regex' => 'Số điện thoại gồm 10 số, bắt đầu bằng 0.',
            'email.required_without' => 'Nhập số điện thoại hoặc email để shop liên hệ lại.',
            'email.email' => 'Email chưa đúng định dạng.',
            'message.required' => 'Bạn chưa nhập lời nhắn.',
            'message.min' => 'Lời nhắn hơi ngắn, tối thiểu 5 ký tự.',
            'message.max' => 'Lời nhắn tối đa 2000 ký tự.',
        ]);

        $key = 'contact:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_HOUR)) {
            return back()->withInput()->withErrors(['message' => 'Bạn gửi hơi nhiều, thử lại sau ít phút nhé.']);
        }

        RateLimiter::hit($key, 3600);
        ContactMessage::create($data + ['ip' => $request->ip()]);

        return redirect('/lien-he')->with('status', self::THANKS);
    }
}
