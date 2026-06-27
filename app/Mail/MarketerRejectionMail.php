<?php

namespace App\Mail;

use App\Models\Marketer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MarketerRejectionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Marketer $marketer,
        public readonly string $reason,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'تحديث بشأن طلب انضمامك كمسوّق',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.marketer.rejection',
        );
    }
}
