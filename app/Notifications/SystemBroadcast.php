<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemBroadcast extends Notification implements ShouldQueue
{
    use Queueable;

    public $title;
    public $message;
    public $actionUrl;

    public function __construct($title, $message, $actionUrl = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
                    ->subject('📢 Info Penting: ' . $this->title)
                    ->greeting('Halo ' . $notifiable->name . '!')
                    ->line($this->message);

        if ($this->actionUrl) {
            $mail->action('Cek Sekarang', $this->actionUrl);
        }

        return $mail->line('Terima kasih telah menjadi bagian dari Hurtsspace Society.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->actionUrl,
            'type' => 'broadcast'
        ];
    }
}
