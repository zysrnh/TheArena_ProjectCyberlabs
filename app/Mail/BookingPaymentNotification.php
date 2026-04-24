<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingPaymentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[The Arena] Pembayaran Masuk - ' . $this->booking->client->name . ' | ' . $this->booking->bill_no,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking.payment-notification',
        );
    }
}