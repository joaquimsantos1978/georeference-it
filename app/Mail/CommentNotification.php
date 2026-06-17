<?php

namespace App\Mail;

use App\Models\LocalityGroup;
use App\Models\LocalityGroupComment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LocalityGroupComment $comment,
        public LocalityGroup $group,
    ) {}

    public function envelope(): Envelope
    {
        $locality = $this->group->verbatim_locality
            ?? $this->group->municipality
            ?? $this->group->county
            ?? 'a locality';

        return new Envelope(
            subject: 'New comment on "' . $locality . '"',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.comment-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
