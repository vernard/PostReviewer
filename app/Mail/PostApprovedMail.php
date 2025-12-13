<?php

namespace App\Mail;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PostApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Post $post,
        public string $approverName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your post has been approved: {$this->post->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.post-approved',
            with: [
                'postTitle' => $this->post->title,
                'approverName' => $this->approverName,
                'postUrl' => config('app.url') . '/posts/' . $this->post->id,
            ],
        );
    }
}
