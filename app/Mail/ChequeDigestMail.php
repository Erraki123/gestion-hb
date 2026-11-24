<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ChequeDigestMail extends Mailable
{
    public function __construct(public string $who, public array $groups, public int $count, public float $sum) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '🔔 ' . $this->count . ' chèque(s) à encaisser — ' . config('commerce.company.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.cheque-digest');
    }
}
