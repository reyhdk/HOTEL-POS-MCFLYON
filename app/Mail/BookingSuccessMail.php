<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking)
    {
        // Memastikan relasi room dan guest ikut terbawa
        $this->booking = $booking->load(['room', 'guest']);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Konfirmasi Pembayaran Booking - ' . $this->booking->midtrans_order_id)
                    ->view('emails.booking_success');
    }
}