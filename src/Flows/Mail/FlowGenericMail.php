<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class FlowGenericMail extends Mailable
{
    public function __construct(public string $subjectLine, public string $bodyHtml) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->bodyHtml);
    }
}
