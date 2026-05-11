<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
class SireneService
{
    private const SEARCH_URL = 'https://recherche-entreprises.api.gouv.fr/search';
    public function lookup(string $identifier): ?array
    {
        $identifier = preg_replace('/\s+/', '', $identifier);
        if (!preg_match('/^\d{9,14}$/', $identifier)) return null;
        return Cache::remember("sirene_{$identifier}", 3600, function () use ($identifier) {
            try {
                $response = Http::timeout(10)->get(self::SEARCH_URL, ['q' => $identifier, 'page' => 1, 'per_page' => 1]);
                if (!$response->successful()) return null;
                $results = $response->json('results', []);
                if (empty($results)) return null;
                return $this->formatResult($results[0]);
            } catch (\Exception $e) { Log::warning("Sirene: " . $e->getMessage()); return null; }
        });
    }
    private function formatResult(array $company): array
    {
        $siege = $company['siege'] ?? [];
        $siren = $company['siren'] ?? '';
        $name = mb_convert_case(mb_strtolower($company['nom_complet'] ?? ''), MB_CASE_TITLE, 'UTF-8');
        return [
            'company_name' => $name, 'siren' => $siren, 'siret' => $siege['siret'] ?? '',
            'vat_number' => strlen($siren) === 9 ? 'FR' . str_pad((string)((12 + 3 * ((int)$siren % 97)) % 97), 2, '0', STR_PAD_LEFT) . $siren : '',
            // Adresse SIREN → sera créée dans la table addresses après sauvegarde
            '_sirene_l4'          => trim(($siege['numero_voie'] ?? '') . ' ' . ($siege['type_voie'] ?? '') . ' ' . ($siege['libelle_voie'] ?? '')),
            '_sirene_postal_code' => $siege['code_postal'] ?? '',
            '_sirene_city'        => $siege['libelle_commune'] ?? '',
            'cedex' => '', 'country' => 'FR', 'status' => $company['etat_administratif'] ?? '',
        ];
    }
    public function search(string $query, int $limit = 5): array
    {
        try {
            $response = Http::timeout(10)->get(self::SEARCH_URL, ['q' => $query, 'page' => 1, 'per_page' => $limit]);
            if (!$response->successful()) return [];
            return array_map(fn($r) => $this->formatResult($r), $response->json('results', []));
        } catch (\Exception $e) { return []; }
    }
}
