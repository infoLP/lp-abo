<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Client;
use App\Models\DuplicateGroup;
use App\Models\DuplicateGroupItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DuplicateDetectionService
{
    protected array $stats = [
        'email' => 0,
        'siret' => 0,
        'name_postal' => 0,
        'phone' => 0,
        'company_city' => 0,
    ];

    public function analyze(): array
    {
        Log::info('DuplicateDetection: Debut de l analyse');

        DuplicateGroup::where('status', 'pending')->delete();
        $dismissedPairs = $this->getDismissedClientPairs();

        DB::beginTransaction();
        try {
            $this->detectByEmail($dismissedPairs);
            $this->detectBySiret($dismissedPairs);
            $this->detectByNamePostal($dismissedPairs);
            $this->detectByPhone($dismissedPairs);
            $this->detectByCompanyCity($dismissedPairs);
            $this->deduplicateGroups();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('DuplicateDetection: Erreur - ' . $e->getMessage());
            throw $e;
        }

        $total = array_sum($this->stats);
        Log::info("DuplicateDetection: Termine - {$total} groupes detectes", $this->stats);
        return $this->stats;
    }

    protected function detectByEmail(array $dismissedPairs): void
    {
        $duplicates = Client::whereNotNull('email')
            ->where('email', '!=', '')
            ->where('status', '!=', 'archived')
            ->select('email', DB::raw('COUNT(*) as cnt'))
            ->groupBy('email')
            ->having('cnt', '>', 1)
            ->pluck('email');

        foreach ($duplicates as $email) {
            $clientIds = Client::where('email', $email)
                ->where('status', '!=', 'archived')
                ->pluck('id')->sort()->values()->toArray();

            if ($this->isPairDismissed($clientIds, $dismissedPairs)) continue;
            $this->createGroup('email', $email, 95, $clientIds);
            $this->stats['email']++;
        }
    }

    protected function detectBySiret(array $dismissedPairs): void
    {
        $duplicates = Client::whereNotNull('siret')
            ->where('siret', '!=', '')
            ->where('status', '!=', 'archived')
            ->select('siret', DB::raw('COUNT(*) as cnt'))
            ->groupBy('siret')
            ->having('cnt', '>', 1)
            ->pluck('siret');

        foreach ($duplicates as $siret) {
            $clientIds = Client::where('siret', $siret)
                ->where('status', '!=', 'archived')
                ->pluck('id')->sort()->values()->toArray();

            if ($this->isPairDismissed($clientIds, $dismissedPairs)) continue;
            $this->createGroup('siret', $siret, 98, $clientIds);
            $this->stats['siret']++;
        }
    }

    protected function detectByNamePostal(array $dismissedPairs): void
    {
        // Détecter les doublons par nom+prénom identiques
        $duplicates = Client::whereNotNull('last_name')
            ->where('last_name', '!=', '')
            ->whereNotNull('first_name')
            ->where('first_name', '!=', '')
            ->where('status', '!=', 'archived')
            ->select(
                DB::raw('LOWER(TRIM(last_name)) as ln'),
                DB::raw('LOWER(TRIM(first_name)) as fn'),
                DB::raw('COUNT(*) as cnt')
            )
            ->groupBy('ln', 'fn')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $clientIds = Client::whereRaw('LOWER(TRIM(last_name)) = ?', [$dup->ln])
                ->whereRaw('LOWER(TRIM(first_name)) = ?', [$dup->fn])
                ->where('status', '!=', 'archived')
                ->pluck('id')->sort()->values()->toArray();

            if (count($clientIds) < 2) continue;
            if ($this->isPairDismissed($clientIds, $dismissedPairs)) continue;

            // Récupérer le CP de la première adresse pour l'affichage
            $firstAddr = \App\Models\Address::whereIn('client_id', $clientIds)->first();
            $postalCode = $firstAddr?->l6_postal_code ?? '';

            $matchValue = ucfirst($dup->fn) . ' ' . strtoupper($dup->ln) . ($postalCode ? ' (' . $postalCode . ')' : '');
            $this->createGroup('name_postal', $matchValue, 80, $clientIds);
            $this->stats['name_postal']++;
        }
    }

    protected function detectByPhone(array $dismissedPairs): void
    {
        // Collecter les numeros phone
        $phones = DB::table('clients')
            ->where('status', '!=', 'archived')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->select('id', DB::raw("REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '.', ''), '-', ''), '+33', '0') as normalized_phone"))
            ->get();

        // Collecter les numeros mobile
        $mobiles = DB::table('clients')
            ->where('status', '!=', 'archived')
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->select('id', DB::raw("REPLACE(REPLACE(REPLACE(REPLACE(mobile, ' ', ''), '.', ''), '-', ''), '+33', '0') as normalized_phone"))
            ->get();

        // Index par telephone normalise
        $phoneIndex = [];
        foreach ($phones as $row) {
            if (strlen($row->normalized_phone) >= 10) {
                $phoneIndex[$row->normalized_phone][] = $row->id;
            }
        }
        foreach ($mobiles as $row) {
            if (strlen($row->normalized_phone) >= 10) {
                $phoneIndex[$row->normalized_phone][] = $row->id;
            }
        }

        foreach ($phoneIndex as $phone => $ids) {
            $uniqueIds = array_values(array_unique($ids));
            sort($uniqueIds);
            if (count($uniqueIds) < 2) continue;
            if ($this->isPairDismissed($uniqueIds, $dismissedPairs)) continue;

            $displayPhone = $phone;
            if (strlen($phone) === 10) {
                $displayPhone = implode(' ', str_split($phone, 2));
            }
            $this->createGroup('phone', $displayPhone, 70, $uniqueIds);
            $this->stats['phone']++;
        }
    }

    protected function detectByCompanyCity(array $dismissedPairs): void
    {
        // Jointure avec addresses pour remplacer la colonne city supprimée
        $duplicates = Client::whereNotNull('company_name')
            ->where('company_name', '!=', '')
            ->whereHas('addresses')
            ->where('status', '!=', 'archived')
            ->select(
                DB::raw('LOWER(TRIM(clients.company_name)) as cn'),
                DB::raw('COUNT(*) as cnt')
            )
            ->groupBy('cn')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $clientIds = Client::whereRaw('LOWER(TRIM(company_name)) = ?', [$dup->cn])
                ->where('status', '!=', 'archived')
                ->whereHas('addresses', function ($q) use ($dup) {
                    // Grouper par ville commune
                    $q->whereIn('client_id', Client::whereRaw('LOWER(TRIM(company_name)) = ?', [$dup->cn])->pluck('id'));
                })
                ->pluck('id')->sort()->values()->toArray();

            if (count($clientIds) < 2) continue;
            if ($this->isPairDismissed($clientIds, $dismissedPairs)) continue;
            $matchValue = ucwords($dup->cn);
            $this->createGroup('company_city', $matchValue, 60, $clientIds);
            $this->stats['company_city']++;
        }
    }

    protected function createGroup(string $type, string $value, int $score, array $clientIds): DuplicateGroup
    {
        $group = DuplicateGroup::create([
            'match_type' => $type,
            'match_value' => $value,
            'confidence_score' => $score,
            'status' => 'pending',
            'clients_count' => count($clientIds),
            'detected_at' => now(),
        ]);

        foreach ($clientIds as $clientId) {
            DuplicateGroupItem::create([
                'duplicate_group_id' => $group->id,
                'client_id' => $clientId,
            ]);
        }
        return $group;
    }

    protected function getDismissedClientPairs(): array
    {
        $dismissed = DuplicateGroup::where('status', 'dismissed')
            ->with('items:id,duplicate_group_id,client_id')
            ->get();

        $pairs = [];
        foreach ($dismissed as $group) {
            $ids = $group->items->pluck('client_id')->sort()->values()->toArray();
            $pairs[implode('-', $ids)] = true;
        }
        return $pairs;
    }

    protected function isPairDismissed(array $clientIds, array $dismissedPairs): bool
    {
        sort($clientIds);
        return isset($dismissedPairs[implode('-', $clientIds)]);
    }

    protected function deduplicateGroups(): void
    {
        $groups = DuplicateGroup::where('status', 'pending')
            ->with('items:id,duplicate_group_id,client_id')
            ->orderByDesc('confidence_score')
            ->get();

        $seen = [];
        foreach ($groups as $group) {
            $key = implode('-', $group->items->pluck('client_id')->sort()->values()->toArray());
            if (isset($seen[$key])) {
                $group->delete();
            } else {
                $seen[$key] = $group->id;
            }
        }
    }

    public static function getStats(): array
    {
        return [
            'pending' => DuplicateGroup::where('status', 'pending')->count(),
            'merged' => DuplicateGroup::where('status', 'merged')->count(),
            'dismissed' => DuplicateGroup::where('status', 'dismissed')->count(),
            'clients_concerned' => DuplicateGroupItem::whereHas('group', fn($query) => $query->where('status', 'pending'))
                ->distinct('client_id')->count('client_id'),
        ];
    }
}
