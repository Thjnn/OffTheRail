<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\OwnerOnly;
use App\Models\Province;
use App\Support\Phone;
use App\Support\ShopSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingsForm extends Component
{
    use OwnerOnly, WithFileUploads;

    /** Các key lưu thẳng (đã chuẩn hoá) vào bảng settings. */
    private const KEYS = [
        'name', 'tagline', 'announcement', 'accent', 'hotline', 'zalo_url', 'messenger_url',
        'free_shipping_threshold', 'shipping_fee_default', 'bank_bin', 'bank_account_no', 'bank_account_name',
        'staff_max_discount_percent', 'notify_email',
    ];

    public string $name = '';

    public string $tagline = '';

    public string $announcement = '';

    public string $accent = '';

    public string $hotline = '';

    public string $zalo_url = '';

    public string $messenger_url = '';

    public ?int $free_shipping_threshold = null;

    public ?int $shipping_fee_default = null;

    /** @var list<array{province_code: string, fee: ?int}> */
    public array $shipping_fees = [];

    public string $bank_bin = '';

    public string $bank_account_no = '';

    public string $bank_account_name = '';

    public string $sizes = '';

    public ?int $staff_max_discount_percent = null;

    public string $notify_email = '';

    public $logo = null;

    public ?string $currentLogo = null;

    public bool $saved = false;

    public function mount(): void
    {
        foreach (self::KEYS as $key) {
            $this->{$key} = shop($key) ?? (is_string($this->{$key}) ? '' : null);
        }

        $this->sizes = implode(', ', (array) shop('sizes'));
        $this->currentLogo = shop('logo');
        $this->shipping_fees = collect((array) shop('shipping_fees'))
            ->map(fn ($fee, $code) => ['province_code' => (string) $code, 'fee' => (int) $fee])->values()->all();
    }

    public function addFeeRow(): void
    {
        $this->shipping_fees[] = ['province_code' => '', 'fee' => null];
    }

    public function removeFeeRow(int $index): void
    {
        unset($this->shipping_fees[$index]);
        $this->shipping_fees = array_values($this->shipping_fees);
    }

    public function save(ShopSettings $settings): void
    {
        $this->saved = false;
        $sizes = $this->parsedSizes();
        $linkRule = ['nullable', 'string', 'max:255', 'url:https', 'starts_with:https://'];

        $data = $this->validate([
            'name' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'announcement' => ['nullable', 'string', 'max:160'],
            'logo' => ['nullable', ...ProductMedia::IMAGE_RULES],
            'accent' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'hotline' => ['nullable', fn ($a, $v, $fail) => $v !== '' && Phone::normalize($v) === null && $fail('Hotline không phải số điện thoại hợp lệ.')],
            'zalo_url' => $linkRule,
            'messenger_url' => $linkRule,
            'free_shipping_threshold' => ['required', 'integer', 'min:0'],
            'shipping_fee_default' => ['required', 'integer', 'min:0'],
            'shipping_fees' => ['array', 'max:40'],
            'shipping_fees.*.province_code' => ['required', 'distinct', 'exists:provinces,code'],
            'shipping_fees.*.fee' => ['required', 'integer', 'min:0'],
            'bank_bin' => ['nullable', 'digits:6', 'required_with:bank_account_no,bank_account_name'],
            'bank_account_no' => ['nullable', 'regex:/^[0-9]{4,20}$/', 'required_with:bank_bin,bank_account_name'],
            'bank_account_name' => ['nullable', 'string', 'max:50', 'required_with:bank_bin,bank_account_no'],
            'sizes' => [fn ($a, $v, $fail) => (count($sizes) < 1 || count($sizes) > 20 || collect($sizes)->contains(fn ($s) => mb_strlen($s) > 20))
                && $fail('Danh sách size cần 1–20 size, mỗi size tối đa 20 ký tự, cách nhau dấu phẩy.')],
            'staff_max_discount_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'notify_email' => ['nullable', 'email'],
        ], [
            'zalo_url.*' => 'Link Zalo phải bắt đầu bằng https://',
            'messenger_url.*' => 'Link Messenger phải bắt đầu bằng https://',
        ], [
            'name' => 'tên shop', 'tagline' => 'khẩu hiệu', 'announcement' => 'thanh thông báo', 'logo' => 'logo',
            'accent' => 'màu chủ đạo', 'free_shipping_threshold' => 'ngưỡng freeship', 'shipping_fee_default' => 'phí ship mặc định',
            'shipping_fees.*.province_code' => 'tỉnh', 'shipping_fees.*.fee' => 'phí ship',
            'bank_bin' => 'mã ngân hàng (BIN)', 'bank_account_no' => 'số tài khoản', 'bank_account_name' => 'tên chủ tài khoản',
            'staff_max_discount_percent' => 'mức giảm tối đa của nhân viên', 'notify_email' => 'email nhận báo đơn',
        ]);

        $values = array_intersect_key($data, array_flip(self::KEYS));
        $values = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $values);
        $values['hotline'] = $values['hotline'] ? Phone::normalize($values['hotline']) : '';
        $values['bank_account_name'] = Str::upper(Str::ascii((string) $values['bank_account_name']));
        $values['sizes'] = $sizes;
        $values['shipping_fees'] = collect($this->shipping_fees)
            ->mapWithKeys(fn ($row) => [(string) $row['province_code'] => (int) $row['fee']])->all();

        if ($this->logo) {
            $values['logo'] = $this->logo->store('branding', 'public');

            if ($this->currentLogo) {
                Storage::disk('public')->delete($this->currentLogo);
            }

            $this->currentLogo = $values['logo'];
            $this->logo = null;
        }

        foreach ($values as $key => $value) {
            $settings->set($key, $value);
        }

        $this->bank_account_name = $values['bank_account_name'];
        $this->hotline = (string) $values['hotline'];
        $this->sizes = implode(', ', $sizes);
        $this->saved = true;
    }

    public function removeLogo(ShopSettings $settings): void
    {
        if ($this->currentLogo) {
            Storage::disk('public')->delete($this->currentLogo);
        }

        $settings->set('logo', null);
        $this->currentLogo = null;
    }

    /** @return list<string> */
    private function parsedSizes(): array
    {
        return array_values(array_unique(array_filter(array_map('trim', explode(',', $this->sizes)), fn ($s) => $s !== '')));
    }

    public function render(): View
    {
        return view('livewire.admin.settings-form', ['provinces' => Province::orderBy('name')->get(['code', 'name'])]);
    }
}
