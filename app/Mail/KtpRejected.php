<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class KtpRejected extends Mailable
{
    use Queueable, SerializesModels;

    public $guest;
    public $reason;

    /**
     * Create a new message instance.
     */
    public function __construct($guest, $reason = "Foto KTP kurang jelas atau tidak sesuai.")
    {
        $this->guest = $guest;
        $this->reason = $reason;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('⚠️ Tindakan Diperlukan: Verifikasi KTP Ditolak')
                    ->view('emails.ktp_rejected');
    }
}