<?php

namespace App\Mail;

use App\Models\VendorAdmin;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamMemberInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly VendorAdmin $member,
        public readonly string $temporaryPassword,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'دعوة للانضمام إلى لوحة البائع',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.vendor.team-member-invite',
        );
    }
}
