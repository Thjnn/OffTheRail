<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** Đơn chuyển khoản chưa trả sau từng này giờ sẽ tự huỷ và hoàn kho. */
    public const TRANSFER_EXPIRY_HOURS = 24;

    // Không có ký tự dễ nhầm (0/O, 1/I). Mã đơn chỉ gồm chữ và số vì nó là nội dung chuyển khoản.
    private const CODE_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'address' => 'array',
            'paid_at' => 'datetime',
            'subtotal' => 'integer',
            'discount' => 'integer',
            'shipping_fee' => 'integer',
            'total' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class)->orderBy('id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public static function generateCode(): string
    {
        do {
            $code = 'DH';
            for ($i = 0; $i < 6; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function isAwaitingTransfer(): bool
    {
        return $this->payment_method === 'bank_transfer'
            && $this->payment_status === 'unpaid'
            && $this->status === OrderStatus::Pending;
    }

    public function paymentLabel(): string
    {
        return match ($this->payment_method) {
            'cod' => 'Thanh toán khi nhận hàng (COD)',
            'bank_transfer' => 'Chuyển khoản ngân hàng',
            'cash' => 'Tiền mặt',
            'qr' => 'Quét mã QR',
            default => $this->payment_method,
        };
    }

    public function addressLine(): string
    {
        $a = $this->address ?? [];

        return implode(', ', array_filter([$a['street'] ?? null, $a['ward'] ?? null, $a['province'] ?? null]));
    }
}
