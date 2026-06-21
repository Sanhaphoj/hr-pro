<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyText,
        public ?string $url = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '['.config('app.name').'] '.$this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            with: [
                'subjectLine' => $this->subjectLine,
                'bodyText' => $this->bodyText,
                'url' => $this->url,
            ],
        );
    }
}
