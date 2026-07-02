<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * LowSmsCreditNotification
 * ------------------------
 * Alerts administrators that the SMS credit balance has dropped below the
 * configured threshold so the account can be topped up before deliveries fail.
 */
class LowSmsCreditNotification extends Notification
{
    use Queueable;

    public function __construct(
        public float $balance,
        public int $threshold,
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
            ->subject('LYVO SMS credit is running low')
            ->greeting('Low SMS credit')
            ->line('The SMS credit balance has fallen below the alert threshold.')
            ->line('Current balance: '.number_format($this->balance).' credits.')
            ->line('Alert threshold: '.number_format($this->threshold).' credits.')
            ->action('Open SMS console', route('admin.sms.index'))
            ->line('Top up the Moolre SMS account to avoid delivery interruptions.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'balance' => $this->balance,
            'threshold' => $this->threshold,
        ];
    }
}
