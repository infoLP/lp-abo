<?php
namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $portalUrl;

    public function __construct(public Client $client)
    {
        $this->portalUrl = route('client.dashboard');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre abonnement numérique est actif — ' . config('app.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.subscription-confirmed');
    }
}
