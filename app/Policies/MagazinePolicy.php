<?php
namespace App\Policies;

use App\Models\Magazine;
use App\Models\User;

class MagazinePolicy
{
    /**
     * Le client peut consulter la liste des numéros d'une publication
     * uniquement s'il a un abonnement actif numérique ou combiné.
     */
    public function view(User $user, Magazine $magazine): bool
    {
        $client = $user->client;
        if (!$client || !$client->isActive()) return false;

        return $client->subscriptions()
            ->where('magazine_id', $magazine->id)
            ->where('status', 'active')
            ->whereIn('support_type', ['digital', 'combined'])
            ->exists();
    }
}
