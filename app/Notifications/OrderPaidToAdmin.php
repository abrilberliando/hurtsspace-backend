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
        // Kirim ke Email Admin & Simpan ke Database (Lonceng Web)
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = env('FRONTEND_URL') . "/admin/orders/" . $this->order->id;

        return (new MailMessage)
                    ->subject('💰 Cuan Masuk! Order #' . $this->order->invoice_number . ' Lunas')
                    ->greeting('Halo Admin G!')
                    ->line('Ada pesanan baru yang sudah lunas. Segera proses pengiriman.')
                    ->line('Total: Rp ' . number_format($this->order->total_price))
                    ->action('Lihat Order', $url);
    }

    public function toArray(object $notifiable): array
    {
        // Data yang disimpan di tabel 'notifications' untuk lonceng
        return [
            'title' => 'Order Paid',
            'message' => 'Order #' . $this->order->invoice_number . ' telah dibayar.',
            'order_id' => $this->order->id,
            'amount' => $this->order->total_price,
            'type' => 'order_paid'
        ];
    }
}
