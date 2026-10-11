<?php

namespace App\Mail;

use App\Domain\Engagement\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ConfirmSubscription extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Subscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Please confirm your subscription');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.confirm-subscription', with: [
            'confirmUrl' => URL::temporarySignedRoute('newsletter.confirm', now()->addDays(7), ['subscriber' => $this->subscriber->id]),
            'unsubscribeUrl' => URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $this->subscriber->id]),
        ]);
    }
}
