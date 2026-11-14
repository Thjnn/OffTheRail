<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Support\OrderFilters;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class OrderTable extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $channel = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $staff = '';

    #[Url]
    public string $q = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    /** @return array<string, string> */
    public function filters(): array
    {
        return array_filter($this->only(['status', 'channel', 'from', 'to', 'staff', 'q']), fn ($v) => $v !== '');
    }

    public function render(): View
    {
        return view('livewire.admin.order-table', [
            'orders' => OrderFilters::apply(Order::query(), $this->filters())->latest('id')->paginate(20),
            'statuses' => OrderStatus::cases(),
            'staffList' => User::orderBy('name')->get(['id', 'name']),
            'isOwner' => auth('admin')->user()->isOwner(),
        ]);
    }
}
