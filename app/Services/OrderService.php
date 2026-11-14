<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\OutOfStockException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Province;
use App\Models\Ward;
use App\Support\Inventory;
use App\Support\Phone;
use App\Support\ShippingFee;
use Illuminate\Support\Facades\DB;
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

        return DB::transaction(function () use ($items, $contact, $paymentMethod, $account, $phone) {
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
    }

    /** Huỷ đơn đang chờ/đã xác nhận và hoàn kho. Trả false nếu đơn không còn huỷ được (kể cả đã huỷ rồi). */
    public function cancel(Order $order, ?string $note = null, ?int $userId = null): bool
    {
        $cancelled = DB::transaction(function () use ($order, $note, $userId) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($locked === null || ! in_array($locked->status, [OrderStatus::Pending, OrderStatus::Confirmed], true)) {
                return false;
            }

            foreach ($locked->items as $item) {
                if ($item->variant_id !== null) {
                    Inventory::putBack($item->variant_id, $item->qty, 'cancel', $locked);
                }
            }

            $from = $locked->status;
            $locked->update(['status' => OrderStatus::Cancelled]);
            $locked->statusLogs()->create([
                'from' => $from->value, 'to' => OrderStatus::Cancelled, 'user_id' => $userId, 'note' => $note,
            ]);

            return true;
        });

        $order->refresh();

        return $cancelled;
    }

    private function label(ProductVariant $variant): string
    {
        return sprintf('%s (%s)', $variant->product?->name, implode(' / ', array_filter([$variant->color?->name, $variant->size])));
    }
}
