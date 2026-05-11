<?php
namespace App\Services;

use App\Mail\SubscriptionConfirmedMail;
use App\Mail\WelcomeNewClientMail;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ClientAccountService
{
    /**
     * Vérifie/crée le compte utilisateur du client et envoie l'email approprié.
     */
    public function handleAccountForClient(Client $client): void
    {
        $email = $client->email;
        if (!$email) return;

        // 1. Chercher via la relation (client_id)
        $user = $client->user;

        // 2. Si pas trouvé via relation → chercher par email dans users
        if (!$user) {
            $user = User::withTrashed()->where('email', $email)->first();

            if ($user) {
                if ($user->trashed()) {
                    // Sécurité : ne pas restaurer un compte supprimé récemment
                    // (peut avoir été supprimé pour raison de sécurité)
                    $daysSinceDeletion = $user->deleted_at->diffInDays(now());

                    if ($daysSinceDeletion < 30) {
                        Log::warning('ClientAccountService: restauration bloquée, compte supprimé récemment', [
                            'user_id'    => $user->id,
                            'email'      => $email,
                            'client_id'  => $client->id,
                            'deleted_at' => $user->deleted_at,
                        ]);
                        return;
                    }

                    Log::info('ClientAccountService: restauration compte supprimé anciennement', [
                        'user_id'   => $user->id,
                        'email'     => $email,
                        'client_id' => $client->id,
                    ]);
                    $user->restore();
                }

                // Rattacher au client si pas encore fait
                if ($user->client_id !== $client->id) {
                    $user->update(['client_id' => $client->id, 'is_active' => true]);
                }
            }
        }

        if (!$user) {
            // ── Cas 1 : aucun compte → créer + email bienvenue ───────────────
            $user = User::create([
                'name'       => $client->display_name,
                'first_name' => $client->first_name,
                'last_name'  => $client->last_name,
                'email'      => $email,
                'password'   => Hash::make(Str::random(32)),
                'is_active'  => true,
                'client_id'  => $client->id,
            ]);
            $user->assignRole('client');

            $this->sendWelcomeEmail($user, $client);

        } elseif (!$user->email_verified_at) {
            // ── Cas 2 : compte existant jamais activé → renvoyer bienvenue ───
            if (!$user->hasRole('client')) {
                $user->assignRole('client');
            }
            $this->sendWelcomeEmail($user, $client);

        } else {
            // ── Cas 3 : compte actif → notification accès portail ────────────
            Mail::to($email)->send(new SubscriptionConfirmedMail($client));
        }
    }

    private function sendWelcomeEmail(User $user, Client $client): void
    {
        $token = Password::createToken($user);
        Mail::to($user->email)->send(new WelcomeNewClientMail($client, $user, $token));
    }
}
