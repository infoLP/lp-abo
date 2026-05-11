<?php

namespace Database\Seeders;

use App\Models\AccountingCode;
use App\Models\AccountingAssignment;
use App\Models\AuxiliaryCode;
use App\Models\Magazine;
use App\Models\VatRate;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        // ── Codes comptables ─────────────────────────────────────────────────
        $codes = [
            ['000 000', 'Inutilisé',                        null,                                           'autre'],
            ['4457 12', 'TVA collectée à 20%',              '4457 - TVA collectée',                         'tva'],
            ['4457 20', 'TVA collectée à 2,10%',            '4457 - TVA collectée',                         'tva'],
            ['707 001', 'Vente EJG',                        '707 - Vente de marchandise',                   'vente'],
            ['707 002', 'Vente IJ',                         '707 - Vente de marchandise',                   'vente'],
            ['707 003', 'Vente LVE',                        '707 - Vente de marchandise',                   'vente'],
            ['707 004', 'Vente LAL',                        '707 - Vente de marchandise',                   'vente'],
            ['707 005', 'Vente 7J',                         '707 - Vente de marchandise',                   'vente'],
            ['708 801', 'Frais de port 20% titre EJG',      '7088 - Autres produits d\'activités annexes',  'livraison'],
            ['708 802', 'Frais de port 20% titre IJ',       '7088 - Autres produits d\'activités annexes',  'livraison'],
            ['708 803', 'Frais de port 20% titre LVE',      '7088 - Autres produits d\'activités annexes',  'livraison'],
            ['708 804', 'Frais de port 20% titre LAL',      '7088 - Autres produits d\'activités annexes',  'livraison'],
            ['708 805', 'Frais de port 20% titre 7J',       '7088 - Autres produits d\'activités annexes',  'livraison'],
        ];

        foreach ($codes as [$code, $label, $description, $type]) {
            AccountingCode::updateOrCreate(
                ['code' => $code],
                compact('label', 'description', 'type')
            );
        }

        // ── Taux de TVA ──────────────────────────────────────────────────────
        $vatRates = [
            [
                'name'  => 'Taux particulier',
                'slug'  => 'taux_particulier',
                'usage' => 'Incluse dans les prix',
                'metropole_accounting_code'        => '4457 20', 'metropole_rate'        => 2.10,
                'corse_accounting_code'            => '4457 20', 'corse_rate'            => 2.10,
                'dom_accounting_code'              => '4457 20', 'dom_rate'              => 1.05,
                'ue_sans_intracom_accounting_code' => '4457 20', 'ue_sans_intracom_rate' => 2.10,
                'ue_avec_intracom_accounting_code' => null,      'ue_avec_intracom_rate' => null,
                'international_accounting_code'    => null,      'international_rate'    => null,
                'sort_order' => 1,
            ],
            [
                'name'  => 'Taux réduit',
                'slug'  => 'taux_reduit',
                'usage' => 'Incluse dans les prix',
                'metropole_accounting_code'        => '000 000', 'metropole_rate'        => 5.50,
                'corse_accounting_code'            => '000 000', 'corse_rate'            => 2.10,
                'dom_accounting_code'              => '000 000', 'dom_rate'              => 2.10,
                'ue_sans_intracom_accounting_code' => '000 000', 'ue_sans_intracom_rate' => 5.50,
                'ue_avec_intracom_accounting_code' => null,      'ue_avec_intracom_rate' => null,
                'international_accounting_code'    => null,      'international_rate'    => null,
                'sort_order' => 2,
            ],
            [
                'name'  => 'Taux normal (produit)',
                'slug'  => 'taux_normal_produit',
                'usage' => 'Incluse dans les prix',
                'metropole_accounting_code'        => '4457 12', 'metropole_rate'        => 20.00,
                'corse_accounting_code'            => '4457 12', 'corse_rate'            => 20.00,
                'dom_accounting_code'              => '4457 12', 'dom_rate'              =>  8.50,
                'ue_sans_intracom_accounting_code' => '4457 12', 'ue_sans_intracom_rate' => 20.00,
                'ue_avec_intracom_accounting_code' => null,      'ue_avec_intracom_rate' => null,
                'international_accounting_code'    => null,      'international_rate'    => null,
                'sort_order' => 3,
            ],
            [
                'name'  => 'Exonération',
                'slug'  => 'exoneration',
                'usage' => 'Incluse dans les prix',
                'sort_order' => 4,
            ],
            [
                'name'  => 'Taux normal (service)',
                'slug'  => 'taux_normal_service',
                'usage' => 'Incluse dans les prix',
                'sort_order' => 5,
            ],
            [
                'name'  => 'Taux intermédiaire',
                'slug'  => 'taux_intermediaire',
                'usage' => 'Incluse dans les prix',
                'sort_order' => 6,
            ],
        ];

        foreach ($vatRates as $data) {
            VatRate::updateOrCreate(['slug' => $data['slug']], $data);
        }

        // ── Affectations comptables ──────────────────────────────────────────
        $magazines  = Magazine::withTrashed()->pluck('id', 'short_name'); // Suppose champ abbreviation
        $vatPresse  = VatRate::where('slug', 'taux_particulier')->first()?->id;
        $vatNormal  = VatRate::where('slug', 'taux_normal_produit')->first()?->id;

        $titres = [
            'EJG' => ['707 001', '708 801'],
            'IJ'  => ['707 002', '708 802'],
            'LVE' => ['707 003', '708 803'],
            'LAL' => ['707 004', '708 804'],
            '7J'  => ['707 005', '708 805'],
        ];

        $sort = 1;
        foreach ($titres as $abbr => [$ventCode, $livrCode]) {
            $magId = $magazines[$abbr] ?? null;

            // Vente abonnement
            AccountingAssignment::updateOrCreate(
                ['label' => "Vente Abonnement {$abbr}", 'type' => 'abonnement'],
                [
                    'magazine_id'  => $magId,
                    'vat_rate_id'  => $vatPresse,
                    'metropole_accounting_code'        => $ventCode,
                    'corse_accounting_code'            => $ventCode,
                    'dom_accounting_code'              => $ventCode,
                    'ue_sans_intracom_accounting_code' => $ventCode,
                    'ue_avec_intracom_accounting_code' => $ventCode,
                    'international_accounting_code'    => $ventCode,
                    'sort_order' => $sort++,
                ]
            );

            // Vente revue
            AccountingAssignment::updateOrCreate(
                ['label' => "Vente Revue {$abbr}", 'type' => 'revue'],
                [
                    'magazine_id'  => $magId,
                    'vat_rate_id'  => $vatPresse,
                    'metropole_accounting_code'        => $ventCode,
                    'corse_accounting_code'            => $ventCode,
                    'dom_accounting_code'              => $ventCode,
                    'ue_sans_intracom_accounting_code' => $ventCode,
                    'ue_avec_intracom_accounting_code' => $ventCode,
                    'international_accounting_code'    => $ventCode,
                    'sort_order' => $sort++,
                ]
            );

            // Livraison
            AccountingAssignment::updateOrCreate(
                ['label' => "Livraison 20% {$abbr}", 'type' => 'livraison'],
                [
                    'magazine_id'  => $magId,
                    'vat_rate_id'  => $vatNormal,
                    'metropole_accounting_code'        => $livrCode,
                    'corse_accounting_code'            => $livrCode,
                    'dom_accounting_code'              => $livrCode,
                    'ue_sans_intracom_accounting_code' => $livrCode,
                    'ue_avec_intracom_accounting_code' => $livrCode,
                    'international_accounting_code'    => $livrCode,
                    'sort_order' => $sort++,
                ]
            );
        }

        // ── Codes auxiliaires ────────────────────────────────────────────────
        $auxiliaires = [
            ['029995', 'Clients Abonnements 7J',  '7J'],
            ['029996', 'Clients Abonnements LAL', 'LAL'],
            ['029997', 'Clients Abonnements LVE', 'LVE'],
            ['029998', 'Clients Abonnements IJ',  'IJ'],
            ['029999', 'Clients Abonnements EJG', 'EJG'],
            ['IMPORT', 'Import',                  null],
            ['WEB',    'Commande en ligne',        null],
        ];

        foreach ($auxiliaires as [$code, $label, $abbr]) {
            AuxiliaryCode::updateOrCreate(
                ['code' => $code],
                [
                    'label'      => $label,
                    'magazine_id'=> $abbr ? ($magazines[$abbr] ?? null) : null,
                ]
            );
        }

        $this->command->info('✅ Données comptables importées avec succès.');
    }
}
