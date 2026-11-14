<?php

namespace App\Livewire\Concerns;

use App\Models\Province;
use App\Models\Ward;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

/** Ô địa chỉ 2 cấp Tỉnh/Thành → Phường/Xã + số nhà, dùng chung cho thanh toán và sổ địa chỉ. */
trait HasAddressFields
{
    public string $provinceCode = '';

    public string $wardCode = '';

    public string $street = '';

    public function updatedProvinceCode(): void
    {
        $this->wardCode = '';
    }

    protected function addressRules(): array
    {
        return [
            'provinceCode' => ['required', 'string', Rule::exists('provinces', 'code')],
            'wardCode' => ['required', 'string', Rule::exists('wards', 'code')->where('province_code', $this->provinceCode)],
            'street' => ['required', 'string', 'max:190'],
        ];
    }

    protected function addressMessages(): array
    {
        return [
            'provinceCode.required' => 'Bạn chọn tỉnh/thành nhé.',
            'provinceCode.exists' => 'Tỉnh/thành không hợp lệ.',
            'wardCode.required' => 'Bạn chọn phường/xã nhé.',
            'wardCode.exists' => 'Phường/xã không thuộc tỉnh/thành đã chọn.',
            'street.required' => 'Bạn nhập số nhà, tên đường nhé.',
            'street.max' => 'Địa chỉ tối đa 190 ký tự.',
        ];
    }

    /** @return Collection<int, Province> */
    #[Computed(persist: true)]
    public function provinces(): Collection
    {
        return Province::orderBy('name')->get();
    }

    /** @return Collection<int, Ward> */
    #[Computed]
    public function wards(): Collection
    {
        return $this->provinceCode === '' ? collect() : Ward::where('province_code', $this->provinceCode)->orderBy('name')->get();
    }
}
