<?php
namespace App\Mail;

use App\Models\Client;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeNewClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $activationUrl;

    public function __construct(
        public Client $client,
        public User   $user,
        public string $token,
    ) {
        $this->activationUrl = url(route('password.set', [
            'token' => $token,
            'email' => $user->email,
        ], false));
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bienvenue — Activez votre accès ' . config('app.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.welcome-new-client');
    }
}
