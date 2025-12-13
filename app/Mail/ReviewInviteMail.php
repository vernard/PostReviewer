<?php

namespace App\Mail;

use App\Models\ApprovalInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ApprovalInvite $invite
    ) {}

    public function envelope(): Envelope
    {
        $post = $this->invite->approvalRequest->post;

        return new Envelope(
            subject: "Review requested: {$post->title}",
        );
    }

    public function content(): Content
    {
        $approvalRequest = $this->invite->approvalRequest;
        $post = $approvalRequest->post;

        return new Content(
            markdown: 'emails.review-invite',
            with: [
                'postTitle' => $post->title,
                'brandName' => $post->brand->name,
                'requesterName' => $approvalRequest->requester->name,
                'reviewUrl' => $this->invite->review_url,
                'expiresAt' => $this->invite->expires_at->format('F j, Y'),
            ],
        );
    }
}
