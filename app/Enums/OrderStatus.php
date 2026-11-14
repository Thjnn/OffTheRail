<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Shipping = 'shipping';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ xác nhận',
            self::Confirmed => 'Đã xác nhận',
            self::Shipping => 'Đang giao',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã huỷ',
            self::Returned => 'Đã hoàn',
        };
    }

    /** Chữ trên nút chuyển sang trạng thái này trong admin. */
    public function actionLabel(): string
    {
        return match ($this) {
            self::Pending => 'Chờ xác nhận',
            self::Confirmed => 'Xác nhận đơn',
            self::Shipping => 'Giao cho vận chuyển',
            self::Completed => 'Đã giao xong',
            self::Cancelled => 'Huỷ đơn',
            self::Returned => 'Ghi nhận hoàn hàng',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Confirmed => 'bg-blue-100 text-blue-800',
            self::Shipping => 'bg-violet-100 text-violet-800',
            self::Completed => 'bg-green-100 text-green-800',
            self::Cancelled, self::Returned => 'bg-zinc-100 text-zinc-600',
        };
    }
}
