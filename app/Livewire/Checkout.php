<?php

namespace App\Livewire;

use App\Exceptions\OutOfStockException;
use App\Livewire\Concerns\HasAddressFields;
use App\Services\OrderService;
use App\Support\Cart;
use App\Support\Phone;
use App\Support\ShippingFee;
use App\Support\VietQr;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Checkout extends Component
{
    use HasAddressFields;

    private const MAX_ORDERS_PER_HOUR = 10;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $note = '';

    public string $paymentMethod = 'cod';

    public string $website = ''; // ô bẫy bot

    public function mount(): void
    {
        if (app(Cart::class)->isEmpty()) {
            $this->redirect('/gio-hang');
        }
    }

    public function place(OrderService $orders): void
    {
        if ($this->website !== '') {
            $this->redirect('/');

            return;
        }

        $data = $this->validate($this->rules(), $this->messages());

        $cart = app(Cart::class);

        if ($cart->isEmpty()) {
            $this->redirect('/gio-hang');

            return;
        }

        $key = 'order:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ORDERS_PER_HOUR)) {
            $this->addError('cart', 'Bạn đặt hơi nhiều đơn, thử lại sau ít phút nhé.');

            return;
        }

        try {
            $order = $orders->placeWebOrder($cart->items(), [
                'name' => trim($data['name']),
                'phone' => $data['phone'],
                'email' => $data['email'] ?: null,
                'province_code' => $data['provinceCode'],
                'ward_code' => $data['wardCode'],
                'street' => trim($data['street']),
                'note' => trim($data['note']) ?: null,
            ], $data['paymentMethod'], auth('web')->user());
        } catch (OutOfStockException $e) {
            $this->addError('cart', 'Rất tiếc, '.implode(', ', $e->names).' không còn đủ hàng. Bạn kiểm tra lại giỏ nhé.');

            return;
        }

        RateLimiter::hit($key, 3600);
        $this->afterPlaced($order);
        $cart->clear();
        $this->redirect('/don-hang/'.$order->code.'/thanh-cong');
    }

    /** Điểm móc cho tính năng tài khoản (lưu địa chỉ); mặc định không làm gì. */
    protected function afterPlaced($order): void {}

    public function render(): View
    {
        $lines = app(Cart::class)->lines();
        $subtotal = (int) $lines->sum(fn ($line) => $line->total());
        $shipping = ShippingFee::for($subtotal, $this->provinceCode ?: null);

        return view('livewire.checkout', [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $subtotal + $shipping,
            'canTransfer' => VietQr::isConfigured(),
        ]);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', function (string $attr, mixed $value, Closure $fail) {
                if (Phone::normalize(is_string($value) ? $value : null) === null) {
                    $fail('Số điện thoại gồm 10 số, bắt đầu bằng 0.');
                }
            }],
            'email' => ['nullable', 'email', 'max:190'],
            'note' => ['nullable', 'string', 'max:500'],
            'paymentMethod' => ['required', Rule::in(VietQr::isConfigured() ? ['cod', 'bank_transfer'] : ['cod'])],
            'website' => ['nullable'],
        ] + $this->addressRules();
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Bạn cho shop xin tên người nhận nhé.',
            'name.max' => 'Tên tối đa 120 ký tự.',
            'phone.required' => 'Bạn nhập số điện thoại để shop gọi khi giao hàng nhé.',
            'email.email' => 'Email chưa đúng định dạng.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
            'paymentMethod.in' => 'Phương thức thanh toán không hợp lệ.',
            'paymentMethod.required' => 'Bạn chọn cách thanh toán nhé.',
        ] + $this->addressMessages();
    }
}
