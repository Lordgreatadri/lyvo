<?php

namespace App\Notifications;

use App\Enums\OtpChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * OtpNotification
 * ---------------
 * Emails a one-time verification code. SMS delivery is handled separately by the
 * OtpService (logged locally) until the SMS gateway is integrated.
 */
class OtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public OtpChannel $channel,
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
        $minutes = (int) config('lyvo.otp.expiry_minutes', 10);

        return (new MailMessage)
            ->subject('Your LYVO verification code')
            ->greeting('Verify your account')
            ->line('Use the code below to verify your '.$this->channel->label().' on LYVO.')
            ->line('**'.$this->code.'**')
            ->line("This code expires in {$minutes} minutes.")
            ->line('If you did not request this, you can safely ignore this email.');
    }
}
