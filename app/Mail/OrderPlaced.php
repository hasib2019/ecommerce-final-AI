<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public bool $forAdmin = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->forAdmin
                ? "New order {$this->order->order_number} – ".money($this->order->total)
                : "Order confirmed: {$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-placed');
    }
}
