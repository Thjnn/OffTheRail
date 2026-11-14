<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email báo chủ shop có đơn web mới. */
class NewWebOrder extends Notification
{
    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $o = $this->order;

        return (new MailMessage)
            ->subject("Đơn mới {$o->code} · ".shop('name'))
            ->greeting('Có đơn web mới!')
            ->line("Khách: {$o->customer_name} · {$o->customer_phone}")
            ->line('Tổng: '.Money::vnd($o->total).' · '.$o->paymentLabel())
            ->action('Xem đơn', url('/admin/don-hang/'.$o->code))
            ->salutation(shop('name'));
    }
}
