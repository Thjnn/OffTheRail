<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\OrderActionException;
use App\Exceptions\OutOfStockException;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Province;
use App\Models\User;
use App\Models\Ward;
use App\Support\Inventory;
use App\Support\Phone;
use App\Support\ShippingFee;
use Illuminate\Database\UniqueConstraintViolationException;
use App\Notifications\NewWebOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderService
{
    /**
     * Tạo đơn web: tính lại giá và phí ship từ DB, trừ kho ngay, tất cả trong một transaction.
     *
     * @param  array<int, int>  $items  [variantId => qty]
     * @param  array{name: string, phone: string, email?: ?string, province_code: string, ward_code: string, street: string, note?: ?string}  $contact  đã validate
     *
     * @throws OutOfStockException khi có món không còn đủ hàng (không có gì được lưu)
     */
    public function placeWebOrder(array $items, array $contact, string $paymentMethod, ?Customer $account = null): Order
    {
        $items = array_filter(array_map('intval', $items), fn (int $qty) => $qty > 0);
        $phone = Phone::normalize($contact['phone'] ?? null);

        if ($items === [] || $phone === null) {
            throw new InvalidArgumentException('Đơn hàng cần ít nhất một món và số điện thoại hợp lệ.');
        }

        $order = DB::transaction(function () use ($items, $contact, $paymentMethod, $account, $phone) {
            $variants = ProductVariant::with('product', 'color')->whereKey(array_keys($items))->get()->keyBy('id');
            $lines = [];
            $unavailable = [];
            $subtotal = 0;

            foreach ($items as $variantId => $qty) {
                $variant = $variants->get($variantId);

                if ($variant === null || ! $variant->product?->isVisible()) {
                    $unavailable[] = $variant ? $this->label($variant) : 'một sản phẩm đã ngừng bán';

                    continue;
                }

                $unitPrice = $variant->price ?? $variant->product->price;
                $subtotal += $unitPrice * $qty;
                $lines[] = [$variant, $qty, $unitPrice];
            }

            $shippingFee = ShippingFee::for($subtotal, $contact['province_code']);

            $order = Order::create([
                'code' => Order::generateCode(),
                'channel' => 'web',
                'customer_id' => ($account ?? Customer::forPhone($phone, $contact['name']))->id,
                'customer_name' => $contact['name'],
                'customer_phone' => $phone,
                'customer_email' => $contact['email'] ?? null,
                'address' => [
                    'province_code' => $contact['province_code'],
                    'province' => Province::whereKey($contact['province_code'])->value('name'),
                    'ward_code' => $contact['ward_code'],
                    'ward' => Ward::whereKey($contact['ward_code'])->value('name'),
                    'street' => $contact['street'],
                ],
                'status' => OrderStatus::Pending,
                'payment_method' => $paymentMethod,
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total' => $subtotal + $shippingFee,
                'note' => $contact['note'] ?? null,
            ]);

            foreach ($lines as [$variant, $qty, $unitPrice]) {
                if (! Inventory::take($variant->id, $qty, 'sale', $order)) {
                    $unavailable[] = $this->label($variant);

                    continue;
                }

                $order->items()->create([
                    'variant_id' => $variant->id,
                    'name' => $variant->product->name,
                    'color' => $variant->color?->name,
                    'size' => $variant->size,
                    'sku' => $variant->sku,
                    'unit_price' => $unitPrice,
                    'qty' => $qty,
                ]);
            }

            if ($unavailable !== []) {
                throw new OutOfStockException($unavailable); // rollback cả đơn và phần kho đã trừ
            }

            $order->statusLogs()->create(['from' => null, 'to' => OrderStatus::Pending]);

            return $order;
        });

        $this->notifyShop($order);

        return $order;
    }

    /** Email báo đơn mới cho shop (nếu đã cấu hình). Gửi lỗi thì chỉ ghi log, đơn vẫn thành công. */
    private function notifyShop(Order $order): void
    {
        $email = shop('notify_email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Notification::route('mail', $email)->notify(new NewWebOrder($order));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Nhập tay một đơn đã bán trên Shopee: trừ kho chung bằng cùng câu UPDATE có điều kiện,
     * đơn hoàn thành + đã trả ngay, không gắn ca (tiền không đi qua két).
     *
     * @param  array<int, int>  $items  [variantId => qty]
     *
     * @throws OrderActionException mã trống hoặc đã nhập
     * @throws OutOfStockException không đủ hàng (không có gì được lưu)
     */
    public function placeShopeeOrder(string $externalCode, array $items, int $amountReceived, User $by): Order
    {
        $code = Str::upper(trim($externalCode));
        $items = array_filter(array_map('intval', $items), fn (int $qty) => $qty > 0);

        if ($code === '') {
            throw new OrderActionException('Cần nhập mã đơn Shopee.');
        }

        if ($items === []) {
            throw new OrderActionException('Đơn cần ít nhất một món.');
        }

        $duplicate = fn () => new OrderActionException("Đơn Shopee {$code} đã được nhập.");

        if (Order::where('channel', 'shopee')->where('external_code', $code)->exists()) {
            throw $duplicate();
        }

        try {
            return DB::transaction(function () use ($code, $items, $amountReceived, $by) {
                [$variants, $subtotal] = $this->priced($items);

                $order = Order::create([
                    'code' => Order::generateCode(),
                    'channel' => 'shopee',
                    'external_code' => $code,
                    'customer_name' => 'Khách Shopee',
                    'status' => OrderStatus::Completed,
                    'payment_method' => 'shopee',
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'subtotal' => $subtotal,
                    'discount' => max(0, $subtotal - $amountReceived),
                    'shipping_fee' => 0,
                    'total' => $amountReceived,
                    'staff_id' => $by->id,
                ]);

                return $this->fillCompleted($order, $variants, $items, $by);
            });
        } catch (UniqueConstraintViolationException) {
            throw $duplicate(); // hai người nhập cùng mã cùng lúc: chỉ mục unique chặn người sau
        }
    }

    /**
     * Đơn bán tại quầy: tạo + trừ kho + hoàn thành + đã trả trong một transaction, gắn vào ca đang mở.
     *
     * @param  array<int, int>  $items  [variantId => qty]
     * @param  'cash'|'qr'  $method
     *
     * @throws OrderActionException chưa mở ca, giảm giá/tiền khách đưa/SĐT không hợp lệ (không có gì được lưu)
     * @throws OutOfStockException không đủ hàng (không có gì được lưu)
     */
    public function placePosOrder(array $items, int $discount, string $method, ?int $cashReceived, ?string $phone, ?string $name, User $by): Order
    {
        $items = array_filter(array_map('intval', $items), fn (int $qty) => $qty > 0);
        $phone = trim((string) $phone);
        $name = trim((string) $name);

        if ($items === []) {
            throw new OrderActionException('Hoá đơn chưa có món nào.');
        }

        if (! in_array($method, ['cash', 'qr'], true)) {
            throw new OrderActionException('Hình thức thanh toán không hợp lệ.');
        }

        $normalizedPhone = null;

        if ($phone !== '' && ($normalizedPhone = Phone::normalize($phone)) === null) {
            throw new OrderActionException('Số điện thoại không hợp lệ.');
        }

        return DB::transaction(function () use ($items, $discount, $method, $cashReceived, $normalizedPhone, $name, $by) {
            $shift = app(ShiftService::class)->current()
                ?? throw new OrderActionException('Chưa mở ca. Mở ca trước khi bán.');

            [$variants, $subtotal] = $this->priced($items);

            if ($discount < 0 || $discount > $subtotal) {
                throw new OrderActionException('Mức giảm giá không hợp lệ.');
            }

            $maxPercent = (int) shop('staff_max_discount_percent');

            if (! $by->isOwner() && $discount > intdiv($subtotal * $maxPercent, 100)) {
                throw new OrderActionException("Nhân viên chỉ được giảm tối đa {$maxPercent}%.");
            }

            $total = $subtotal - $discount;

            if ($method === 'cash' && (int) $cashReceived < $total) {
                throw new OrderActionException('Tiền khách đưa chưa đủ.');
            }

            $customer = $normalizedPhone ? Customer::forPhone($normalizedPhone, $name ?: 'Khách tại quầy') : null;

            $order = Order::create([
                'code' => Order::generateCode(),
                'channel' => 'pos',
                'customer_id' => $customer?->id,
                'customer_name' => $customer?->name ?? 'Khách lẻ',
                'customer_phone' => $normalizedPhone,
                'status' => OrderStatus::Completed,
                'payment_method' => $method,
                'payment_status' => 'paid',
                'paid_at' => now(),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_fee' => 0,
                'total' => $total,
                'cash_received' => $method === 'cash' ? $cashReceived : null,
                'staff_id' => $by->id,
                'shift_id' => $shift->id,
            ]);

            return $this->fillCompleted($order, $variants, $items, $by);
        });
    }

    /**
     * Nạp biến thể và tính tạm tính theo giá DB.
     *
     * @param  array<int, int>  $items
     * @return array{0: \Illuminate\Support\Collection<int, ProductVariant>, 1: int}
     */
    private function priced(array $items): array
    {
        $variants = ProductVariant::with('product', 'color')->whereKey(array_keys($items))->get()->keyBy('id');
        $subtotal = 0;

        foreach ($items as $variantId => $qty) {
            $variant = $variants->get($variantId) ?? throw new OutOfStockException(['một sản phẩm đã bị xoá']);
            $subtotal += ($variant->price ?? $variant->product->price) * $qty;
        }

        return [$variants, $subtotal];
    }

    /** Trừ kho + ghi món + log "hoàn thành" cho đơn quầy/Shopee. Thiếu hàng → OutOfStockException (transaction ngoài rollback). */
    private function fillCompleted(Order $order, $variants, array $items, User $by): Order
    {
        $unavailable = [];

        foreach ($items as $variantId => $qty) {
            $variant = $variants->get($variantId);

            if (! Inventory::take($variant->id, $qty, 'sale', $order, $by->id)) {
                $unavailable[] = $this->label($variant);

                continue;
            }

            $order->items()->create([
                'variant_id' => $variant->id,
                'name' => $variant->product->name,
                'color' => $variant->color?->name,
                'size' => $variant->size,
                'sku' => $variant->sku,
                'unit_price' => $variant->price ?? $variant->product->price,
                'qty' => $qty,
            ]);
        }

        if ($unavailable !== []) {
            throw new OutOfStockException($unavailable);
        }

        $order->statusLogs()->create(['from' => null, 'to' => OrderStatus::Completed, 'user_id' => $by->id]);

        return $order;
    }

    /** Huỷ đơn đang chờ/đã xác nhận và hoàn kho (lệnh tự huỷ). Trả false nếu đơn không còn huỷ được (kể cả đã huỷ rồi). */
    public function cancel(Order $order, ?string $note = null, ?int $userId = null): bool
    {
        $cancelled = DB::transaction(function () use ($order, $note, $userId) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($locked === null || ! in_array($locked->status, [OrderStatus::Pending, OrderStatus::Confirmed], true)) {
                return false;
            }

            $this->restock($locked, OrderStatus::Cancelled, $userId, $note);

            return true;
        });

        $order->refresh();

        return $cancelled;
    }

    /** @return list<OrderStatus> các trạng thái đơn được phép chuyển sang từ trạng thái hiện tại */
    public function nextStatuses(Order $order): array
    {
        if ($order->channel !== 'web') {
            // Đơn Shopee/quầy tạo ra đã hoàn thành; chỉ còn đường huỷ (hoàn kho).
            return $order->status === OrderStatus::Completed ? [OrderStatus::Cancelled] : [];
        }

        return match ($order->status) {
            OrderStatus::Pending => [OrderStatus::Confirmed, OrderStatus::Cancelled],
            OrderStatus::Confirmed => [OrderStatus::Shipping, OrderStatus::Cancelled],
            OrderStatus::Shipping => [OrderStatus::Completed, OrderStatus::Returned],
            OrderStatus::Completed => [OrderStatus::Returned],
            OrderStatus::Cancelled, OrderStatus::Returned => [],
        };
    }

    /**
     * Chuyển trạng thái đơn. Kiểm tra trên bản ghi đã khoá, nên hai người bấm cùng lúc
     * (hay bấm hai lần) thì người sau nhận thông báo, kho không bị hoàn hai lần.
     *
     * @throws OrderActionException
     */
    public function transition(Order $order, OrderStatus $to, User $by, ?string $note = null, ?string $trackingCode = null): void
    {
        DB::transaction(function () use ($order, $to, $by, $note, $trackingCode) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! in_array($to, $this->nextStatuses($locked), true)) {
                throw new OrderActionException("Đơn đang ở trạng thái «{$from->label()}», không chuyển sang «{$to->label()}» được.");
            }

            if ($to === OrderStatus::Shipping) {
                $trackingCode = trim((string) $trackingCode);

                if ($trackingCode === '') {
                    throw new OrderActionException('Cần nhập mã vận đơn.');
                }

                $locked->tracking_code = $trackingCode;
            }

            if (in_array($to, [OrderStatus::Cancelled, OrderStatus::Returned], true)) {
                $wasPaid = $locked->payment_status === 'paid';

                if (($wasPaid || $from === OrderStatus::Completed) && ! $by->isOwner()) {
                    throw new OrderActionException('Chỉ chủ shop được huỷ/hoàn đơn đã thanh toán.');
                }

                if ($wasPaid) {
                    $locked->payment_status = 'refunded';
                }

                $this->restock($locked, $to, $by->id, $note);

                ActivityLog::record($to === OrderStatus::Cancelled ? 'order.cancel' : 'order.return', $locked,
                    ['status' => $from->value], ['status' => $to->value, 'payment_status' => $locked->payment_status]);

                return;
            }

            if ($to === OrderStatus::Completed && $locked->payment_status === 'unpaid') {
                $locked->payment_status = 'paid'; // COD: tiền thu khi giao xong
                $locked->paid_at = now();
            }

            $locked->status = $to;
            $locked->save();
            $locked->statusLogs()->create(['from' => $from->value, 'to' => $to, 'user_id' => $by->id, 'note' => $note]);
        });

        $order->refresh();
    }

    /** Xác nhận đã nhận tiền (chuyển khoản, hoặc COD thu trước). @throws OrderActionException */
    public function markPaid(Order $order, User $by): void
    {
        DB::transaction(function () use ($order, $by) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->payment_status !== 'unpaid' || in_array($locked->status, [OrderStatus::Cancelled, OrderStatus::Returned], true)) {
                throw new OrderActionException('Đơn này không ở trạng thái chờ thanh toán.');
            }

            $locked->update(['payment_status' => 'paid', 'paid_at' => now()]);
            $locked->statusLogs()->create([
                'from' => $locked->status->value, 'to' => $locked->status, 'user_id' => $by->id, 'note' => 'Đã nhận tiền',
            ]);
        });

        $order->refresh();
    }

    /** Hoàn kho từng món rồi đặt trạng thái huỷ/hoàn. Gọi trong transaction, với đơn đã khoá. */
    private function restock(Order $locked, OrderStatus $to, ?int $userId, ?string $note): void
    {
        $reason = $to === OrderStatus::Returned ? 'return' : 'cancel';

        foreach ($locked->items as $item) {
            if ($item->variant_id !== null) {
                Inventory::putBack($item->variant_id, $item->qty, $reason, $locked, $userId);
            }
        }

        $from = $locked->status;
        $locked->status = $to;
        $locked->save();
        $locked->statusLogs()->create(['from' => $from->value, 'to' => $to, 'user_id' => $userId, 'note' => $note]);
    }

    private function label(ProductVariant $variant): string
    {
        return sprintf('%s (%s)', $variant->product?->name, implode(' / ', array_filter([$variant->color?->name, $variant->size])));
    }
}
