<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\SalesReport;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class Dashboard extends Component
{
    public const RANGES = [7 => '7 ngày qua', 30 => '30 ngày qua', 90 => '90 ngày qua'];

    #[Url]
    public int $range = 7;

    public static function greeting(int $hour, string $name): string
    {
        $part = match (true) {
            $hour < 11 => 'sáng',
            $hour < 18 => 'chiều',
            default => 'tối',
        };

        return "Chào buổi {$part}, {$name}";
    }

    public function render(): View
    {
        if (! array_key_exists($this->range, self::RANGES)) {
            $this->range = 7;
        }

        $user = auth('admin')->user();
        $report = new SalesReport(now()->subDays($this->range - 1), now(), $user->isOwner() ? null : $user->id);
        $previous = $report->previous();
        $revenue = $report->revenue();
        $orders = $report->orderCount();

        return view('livewire.admin.dashboard', [
            'user' => $user,
            'greeting' => self::greeting((int) now()->format('G'), $user->name),
            'revenue' => $revenue,
            'revenueChange' => SalesReport::change($revenue, $previous->revenue()),
            'orders' => $orders,
            'ordersChange' => SalesReport::change($orders, $previous->orderCount()),
            'pending' => Order::where('channel', 'web')->where('status', OrderStatus::Pending)->count(),
            'lowStockCount' => ProductVariant::lowStock()->count(),
            'lowStock' => ProductVariant::lowStock()->with('product', 'color')->orderBy('stock')->limit(8)->get(),
            'latest' => Order::latest('id')->limit(8)->get(),
            'chart' => $report->daily(),
        ]);
    }
}
