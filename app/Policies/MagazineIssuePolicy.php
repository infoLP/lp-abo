<?php
namespace App\Policies;

use App\Models\MagazineIssue;
use App\Models\User;

class MagazineIssuePolicy
{
    /**
     * Le client peut lire un numéro s'il a un abonnement actif valide
     * pour la publication correspondante, sur la période du numéro.
     */
    public function view(User $user, MagazineIssue $issue): bool
    {
        $client = $user->client;
        if (!$client) return false;

        return $client->canAccessIssue($issue);
    }

    /**
     * Stream PDF : même règle que view.
     */
    public function stream(User $user, MagazineIssue $issue): bool
    {
        return $this->view($user, $issue);
    }
}
