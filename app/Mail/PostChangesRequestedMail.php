<?php

namespace App\Mail;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PostChangesRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Post $post,
        public string $reviewerName,
        public ?string $feedback = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Changes requested for: {$this->post->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.post-changes-requested',
            with: [
                'postTitle' => $this->post->title,
                'reviewerName' => $this->reviewerName,
                'feedback' => $this->feedback,
                'postUrl' => config('app.url') . '/posts/' . $this->post->id,
            ],
        );
    }
}
