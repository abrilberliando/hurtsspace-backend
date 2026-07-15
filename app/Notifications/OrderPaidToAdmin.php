<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPaidToAdmin extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via(object $notifiable): array
    {
        // Send to Admin Email & Save to Database (Web Bell)
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = env('FRONTEND_URL') . "/admin/orders/";

        return (new MailMessage)
                    ->subject('💰 Payment Received! Order #' . $this->order->invoice_number . ' Paid')
                    ->greeting('Hello Admin!')
                    ->line('There is a new paid order. Please process the shipping.')
                    ->line('Total: Rp ' . number_format($this->order->total_price))
                    ->action('View Order', $url);
    }

    public function toArray(object $notifiable): array
    {
        // Data stored in 'notifications' table for bell
        return [
            'title' => 'Order Paid',
            'message' => 'Order #' . $this->order->invoice_number . ' has been paid.',
            'order_id' => $this->order->id,
            'amount' => $this->order->total_price,
            'type' => 'order_paid'
        ];
    }
}
