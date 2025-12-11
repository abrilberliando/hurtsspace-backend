<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $url;

    public function __construct($user, $url)
    {
        $this->user = $user;
        $this->url = $url;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔒 Reset Password Hurtsspace',
        );
    }

    public function content(): Content
    {
        // Kita pake view inline sederhana aja biar cepet, atau lo bisa bikin blade view
        return new Content(
            view: 'emails.auth.reset',
        );
    }
}
