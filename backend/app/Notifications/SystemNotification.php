<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?int $orderId = null,
        public ?string $actionUrl = null,
        public string $actionText = 'View Order'
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tindahan - {$this->title}")
            ->view('emails.tindahan-notification', [
                'name' => $notifiable->full_name,
                'title' => $this->title,
                'body' => $this->message,
                'orderId' => $this->orderId,
                'actionUrl' => $this->actionUrl,
                'actionText' => $this->actionText,
            ]);
    }
}
