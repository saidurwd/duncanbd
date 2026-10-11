<?php

namespace App\Mail;

use App\Domain\Engagement\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Internal notification to the routed inbox. Reply-To is the enquirer so staff can answer directly. */
class EnquiryReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Enquiry $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->enquiry->email, preg_replace('/[\r\n]+/', ' ', $this->enquiry->name))],
            subject: '[Website enquiry] '.$this->enquiry->category->label(),
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.enquiry-received');
    }
}
