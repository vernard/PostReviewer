<?php

namespace App\Mail;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PostSubmittedForApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Post $post
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New post needs your approval: {$this->post->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.post-submitted',
            with: [
                'postTitle' => $this->post->title,
                'creatorName' => $this->post->creator->name,
                'brandName' => $this->post->brand->name,
                'reviewUrl' => config('app.url') . '/posts/' . $this->post->id,
            ],
        );
    }
}
