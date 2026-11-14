<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\OwnerOnly;
use App\Models\Customer;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerTable extends Component
{
    use OwnerOnly, WithPagination;

    public const SORTS = ['spent' => 'Chi tiêu nhiều nhất', 'orders' => 'Nhiều đơn nhất', 'newest' => 'Mới nhất'];

    #[Url]
    public string $q = '';

    #[Url]
    public string $sort = 'spent';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $q = trim($this->q);
        $phone = Phone::normalize($q);
        $sort = array_key_exists($this->sort, self::SORTS) ? $this->sort : 'spent';

        $customers = Customer::query()
            ->when($q !== '', fn ($query) => $phone
                ? $query->where('phone', $phone)
                : $query->where('name', 'like', "%{$q}%"))
            ->when($sort === 'spent', fn ($query) => $query->orderByDesc('total_spent'))
            ->when($sort === 'orders', fn ($query) => $query->orderByDesc('orders_count'))
            ->orderByDesc('id')
            ->paginate(25);

        return view('livewire.admin.customer-table', ['customers' => $customers]);
    }
}
