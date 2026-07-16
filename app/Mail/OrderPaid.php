<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPaid extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Payment Received! Order #' . $this->order->invoice_number . ' Processed',
        );
    }

    public function content(): Content
    {
        // Lo bisa pake view yang sama kayak OrderPlaced atau bikin baru
        // Kita reuse aja view 'placed' tapi kasih flag 'is_paid'
        return new Content(
            view: 'emails.orders.placed',
            with: ['is_paid' => true],
        );
    }
}
