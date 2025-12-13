<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invitation $invitation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to join {$this->invitation->agency->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invitation',
            with: [
                'acceptUrl' => config('app.url') . '/invitation/' . $this->invitation->token,
                'inviterName' => $this->invitation->inviter->name,
                'agencyName' => $this->invitation->agency->name,
                'role' => ucfirst($this->invitation->role),
                'expiresAt' => $this->invitation->expires_at->format('F j, Y'),
            ],
        );
    }
}
