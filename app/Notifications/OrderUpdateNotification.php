<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * OrderUpdateNotification
 * -----------------------
 * Emails a party (buyer or seller) when an order's escrow status changes. SMS is
 * sent separately by the EscrowService via the send_sms() helper so both
 * channels share one message but use their own gateways.
 */
class OrderUpdateNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $headline,
        public string $body,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('LYVO order '.$this->order->order_number.' — '.$this->headline)
            ->greeting($this->headline)
            ->line($this->body)
            ->line('Order: '.$this->order->order_number)
            ->line('Status: '.$this->order->status->label())
            ->action('View order', url('/dashboard'))
            ->line('Thank you for using LYVO Escrow.');
    }
}
