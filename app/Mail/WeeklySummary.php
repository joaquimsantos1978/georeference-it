<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklySummary extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $suggestions,
        public int $validations,
        public int $comments,
        public int $totalContributors,
        public int $totalGeoreferenced,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your weekly summary on georeference.it',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.weekly-summary',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
