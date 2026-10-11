<?php

namespace App\Mail;

use App\Domain\Careers\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to HR (no CV attached: they open it from the admin) and, with $forApplicant, to the candidate. */
class ApplicationReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Application $application, public bool $forApplicant = false) {}

    public function envelope(): Envelope
    {
        $title = $this->application->vacancy->title;

        return new Envelope(subject: $this->forApplicant ? "We received your application: $title" : "[Careers] New application: $title");
    }

    public function content(): Content
    {
        return new Content(text: $this->forApplicant ? 'mail.application-acknowledgement' : 'mail.application-received');
    }
}
