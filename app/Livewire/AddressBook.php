<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasAddressFields;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Support\Phone;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AddressBook extends Component
{
    use HasAddressFields;

    /** null = không mở form; 0 = đang thêm mới; > 0 = đang sửa địa chỉ đó */
    public ?int $editingId = null;

    public string $name = '';

    public string $phone = '';

    public function create(): void
    {
        $customer = $this->customer();
        $this->resetValidation();
        $this->editingId = 0;
        $this->name = $customer->name;
        $this->phone = $customer->phone;
        $this->provinceCode = $this->wardCode = $this->street = '';
    }

    public function edit($id): void
    {
        $address = $this->find($id);
        $this->resetValidation();
        $this->editingId = $address->id;
        $this->fill($address->only('name', 'phone', 'street'));
        $this->provinceCode = $address->province_code;
        $this->wardCode = $address->ward_code;
    }

    public function cancel(): void
    {
        $this->editingId = null;
    }

    public function save(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', function (string $attr, mixed $value, Closure $fail) {
                if (Phone::normalize(is_string($value) ? $value : null) === null) {
                    $fail('Số điện thoại gồm 10 số, bắt đầu bằng 0.');
                }
            }],
        ] + $this->addressRules(), ['name.required' => 'Bạn nhập tên người nhận nhé.'] + $this->addressMessages());

        $customer = $this->customer();
        $attributes = [
            'name' => trim($data['name']),
            'phone' => Phone::normalize($data['phone']),
            'province_code' => $data['provinceCode'],
            'ward_code' => $data['wardCode'],
            'street' => trim($data['street']),
        ];

        if ($this->editingId > 0) {
            $this->find($this->editingId)->update($attributes);
        } elseif (! self::store($customer, $attributes)) {
            $this->addError('street', 'Bạn lưu tối đa '.Customer::MAX_ADDRESSES.' địa chỉ.');

            return;
        }

        $this->editingId = null;
    }

    /** Thêm địa chỉ cho khách (dùng chung với trang thanh toán). Trả false nếu đã đủ số tối đa. */
    public static function store(Customer $customer, array $attributes): bool
    {
        $count = $customer->addresses()->count();

        if ($count >= Customer::MAX_ADDRESSES) {
            return false;
        }

        $customer->addresses()->create($attributes + ['is_default' => $count === 0]);

        return true;
    }

    public function delete($id): void
    {
        $address = $this->find($id);

        DB::transaction(function () use ($address) {
            $address->delete();

            if ($address->is_default) {
                $this->customer()->addresses()->reorder()->latest('id')->first()?->update(['is_default' => true]);
            }
        });

        if ($this->editingId === $address->id) {
            $this->editingId = null;
        }
    }

    public function makeDefault($id): void
    {
        $address = $this->find($id);

        DB::transaction(function () use ($address) {
            $this->customer()->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });
    }

    public function render(): View
    {
        return view('livewire.address-book', [
            'addresses' => $this->customer()->addresses()->with('province', 'ward')->get(),
        ]);
    }

    private function find($id): CustomerAddress
    {
        return $this->customer()->addresses()->whereKey(is_numeric($id) ? (int) $id : 0)->firstOrFail();
    }

    private function customer(): Customer
    {
        return auth('web')->user() ?? abort(403);
    }
}
