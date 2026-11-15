<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shift;
use App\Models\User;
use App\Models\Ward;
use App\Services\OrderService;
use App\Services\ShiftService;
use App\Support\Inventory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * ~200 đơn trải đều 60 ngày qua (Web ~55%, Quầy ~25% trong các ca đã chốt, Shopee ~20%) để biểu đồ và các bảng admin có số liệu.
 * Đơn đi qua đúng OrderService nên kho, log trạng thái và số liệu khách đều khớp. Chạy lại không tạo thêm.
 */
class DemoOrderSeeder extends Seeder
{
    private const DAYS = 60;

    private const ORDERS = 200;

    private Carbon $realNow;

    /** Ca bán tại quầy đang mở trong lúc seed. */
    private ?Shift $shift = null;

    public function run(OrderService $orders, ShiftService $shifts): void
    {
        if (Order::count() >= 100) {
            return;
        }

        mt_srand(2026);
        fake()->seed(2026);
        $this->realNow = now()->copy();

        $owner = User::where('role', 'owner')->firstOrFail();
        $staff = User::where('role', 'staff')->get();
        $variants = ProductVariant::whereIn('product_id', Product::visible()->select('id'))->with('product')->get();
        $wards = Ward::inRandomOrder()->limit(60)->get(['code', 'province_code']);
        $customers = collect(range(1, 40))->map(fn () => [
            'name' => fake('vi_VN')->name(),
            'phone' => '09'.str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT),
        ]);

        // Đơn chỉ bắt đầu từ lúc sản phẩm đầu tiên lên kệ (tối đa 60 ngày).
        $firstLaunch = $variants->min(fn ($v) => $v->product->published_at ?? $this->realNow->copy()->subDays(self::DAYS));
        $days = max(1, min(self::DAYS, (int) $firstLaunch->diffInDays($this->realNow, true)));

        try {
            foreach ($this->timestamps($days) as $at) {
                Carbon::setTestNow($at);
                // Chỉ bán món đã đăng bán tại thời điểm đó.
                $available = $variants->filter(fn ($v) => $v->product->published_at === null || $v->product->published_at->lte($at));
                $items = $this->pickItems($available, $owner);

                $subtotal = collect($items)->sum(fn ($qty, $id) => ($variants->find($id)->price ?? $variants->find($id)->product->price) * $qty);
                $channel = mt_rand(1, 100);

                if ($channel <= 20) {
                    $code = $at->format('ymd').strtoupper(fake()->bothify('??####'));
                    $orders->placeShopeeOrder($code, $items, (int) (round($subtotal * 0.9 / 1000) * 1000), $staff->random());

                    continue;
                }

                if ($channel <= 45) {
                    $this->counterSale($orders, $shifts, $items, $subtotal, $at, $staff, $customers);

                    continue;
                }

                $who = $customers->random();
                $ward = $wards->random();
                $method = mt_rand(1, 100) <= 25 ? 'bank_transfer' : 'cod';
                $order = $orders->placeWebOrder($items, [
                    'name' => $who['name'], 'phone' => $who['phone'], 'email' => null,
                    'province_code' => $ward->province_code, 'ward_code' => $ward->code,
                    'street' => mt_rand(1, 200).' '.fake('vi_VN')->streetName(), 'note' => null,
                ], $method);

                $this->advance($orders, $order, $at, $staff->random());
            }

            // Ca cuối: hôm nay thì để mở cho demo bán được ngay, ngày cũ thì chốt.
            if ($this->shift && ! $this->shift->opened_at->isSameDay($this->realNow)) {
                $this->closeShift($shifts);
            }
        } finally {
            Carbon::setTestNow();
        }

        // Vài biến thể sắp hết để thẻ "Sắp hết" trên Tổng quan có số.
        foreach ($variants->shuffle()->take(5) as $variant) {
            Inventory::adjust($variant->id, 'count', mt_rand(1, 2), 'Kiểm kho demo', $owner);
        }
    }

    /**
     * Một đơn tại quầy trong ca của ngày đó: ca mở lúc 8h (1 triệu đầu ca), chốt 22h.
     * 70% tiền mặt (khách đưa tròn 50k), 30% QR; 30% có SĐT khách; 15% được giảm 5%.
     */
    private function counterSale(OrderService $orders, ShiftService $shifts, array $items, int $subtotal, Carbon $at, Collection $staff, Collection $customers): void
    {
        if ($this->shift && ! $this->shift->opened_at->isSameDay($at)) {
            $this->closeShift($shifts);
        }

        if (! $this->shift) {
            Carbon::setTestNow($at->copy()->setTime(8, 0)->min($at));
            $this->shift = $shifts->open(1000000, $staff->random());
            Carbon::setTestNow($at);
        }

        $discount = mt_rand(1, 100) <= 15 ? (int) (floor($subtotal * 0.05 / 1000) * 1000) : 0;
        $cash = mt_rand(1, 100) <= 70;
        $who = mt_rand(1, 100) <= 30 ? $customers->random() : null;

        $orders->placePosOrder($items, $discount, $cash ? 'cash' : 'qr',
            $cash ? (int) (ceil(($subtotal - $discount) / 50000) * 50000) : null,
            $who['phone'] ?? null, $who['name'] ?? null, $staff->random());
    }

    /** Chốt ca lúc 22h: đa số đếm khớp, ~20% ca lệch từ −20k đến +10k. */
    private function closeShift(ShiftService $shifts): void
    {
        Carbon::setTestNow($this->shift->opened_at->copy()->setTime(22, 0)->min($this->realNow));
        $expected = $this->shift->opening_cash + $this->shift->cashSales();
        $off = mt_rand(1, 100) <= 20 ? (mt_rand(0, 1) ? -mt_rand(1, 10) : mt_rand(1, 5)) * 2000 : 0;
        $shifts->close($this->shift, $expected + $off, $this->shift->user, $off ? 'Lệch khi đếm két' : null);
        $this->shift = null;
    }

    /** @return list<Carbon> mốc thời gian tăng dần, cuối tuần dày hơn, giờ 8h–22h */
    private function timestamps(int $days): array
    {
        $now = $this->realNow;
        $times = [];

        for ($i = 0; $i < self::ORDERS; $i++) {
            do {
                $day = $now->copy()->subDays(mt_rand(0, $days - 1));
            } while (! $day->isWeekend() && mt_rand(1, 100) <= 30); // ngày thường bị bỏ bớt

            $at = $day->setTime(mt_rand(8, 21), mt_rand(0, 59));
            $times[] = $at->gt($now) ? $now->copy()->subMinutes(mt_rand(5, 120)) : $at;
        }

        usort($times, fn (Carbon $a, Carbon $b) => $a <=> $b);

        return $times;
    }

    /** @return array<int, int> [variantId => qty]; nhập thêm hàng nếu biến thể sắp cạn */
    private function pickItems(Collection $variants, User $owner): array
    {
        $items = [];

        foreach ($variants->random(min($variants->count(), mt_rand(1, 3))) as $variant) {
            $qty = mt_rand(1, 100) <= 85 ? 1 : 2;

            if ($variant->fresh()->stock < $qty + 2) {
                Inventory::adjust($variant->id, 'import', 20, 'Nhập hàng demo', $owner);
            }

            $items[$variant->id] = $qty;
        }

        return $items;
    }

    /** Đẩy đơn web tới trạng thái hợp với tuổi của nó. */
    private function advance(OrderService $orders, Order $order, Carbon $at, User $by): void
    {
        $age = (int) $at->diffInDays($this->realNow, true);
        $roll = mt_rand(1, 100);
        // Các bước sau xảy ra muộn hơn lúc đặt, nhưng không vượt quá hiện tại.
        $at = fn (int $minutes) => Carbon::setTestNow($order->created_at->copy()->addMinutes($minutes)->min($this->realNow));

        $step = function (OrderStatus $to, int $hours, ?string $tracking = null) use ($orders, $order, $at, $by) {
            $at($hours * 60);
            $orders->transition($order, $to, $by, null, $tracking);
        };

        if ($order->payment_method === 'bank_transfer' && ($age >= 1 || $roll <= 60)) {
            $at(mt_rand(10, 300));
            $orders->markPaid($order, $by);
        }

        if ($age <= 1) {
            if ($roll <= 40) {
                $step(OrderStatus::Confirmed, 1);
            }

            return;
        }

        if ($roll <= 8 && $order->payment_status !== 'paid') {
            $step(OrderStatus::Cancelled, 3); // khách đổi ý trước khi giao

            return;
        }

        $step(OrderStatus::Confirmed, 2);

        if ($age <= 3 && $roll <= 50) {
            return;
        }

        $step(OrderStatus::Shipping, 20, 'GHN'.mt_rand(100000000, 999999999));

        if ($age <= 5) {
            return;
        }

        if ($roll <= 12 && $order->payment_status !== 'paid') {
            $step(OrderStatus::Returned, 72); // khách bom hàng COD

            return;
        }

        $step(OrderStatus::Completed, 48);
    }
}
