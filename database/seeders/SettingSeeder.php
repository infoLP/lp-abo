<?php
namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [

            // ── Société ────────────────────────────────────────────────────────
            ['group'=>'company','key'=>'company.name',        'label'=>'Nom de la société',      'type'=>'text',    'default_value'=>'LPAbonnements',         'sort_order'=>1],
            ['group'=>'company','key'=>'company.address1',    'label'=>'Adresse ligne 1',         'type'=>'text',    'default_value'=>'',                      'sort_order'=>2],
            ['group'=>'company','key'=>'company.address2',    'label'=>'Adresse ligne 2',         'type'=>'text',    'default_value'=>'',                      'sort_order'=>3],
            ['group'=>'company','key'=>'company.postal_code', 'label'=>'Code postal',             'type'=>'text',    'default_value'=>'',                      'sort_order'=>4],
            ['group'=>'company','key'=>'company.city',        'label'=>'Ville',                   'type'=>'text',    'default_value'=>'',                      'sort_order'=>5],
            ['group'=>'company','key'=>'company.country',     'label'=>'Pays',                    'type'=>'text',    'default_value'=>'France',                'sort_order'=>6],
            ['group'=>'company','key'=>'company.siret',       'label'=>'SIRET',                   'type'=>'text',    'default_value'=>'',                      'sort_order'=>7],
            ['group'=>'company','key'=>'company.vat_number',  'label'=>'N° TVA intracommunautaire','type'=>'text',   'default_value'=>'',                      'sort_order'=>8],
            ['group'=>'company','key'=>'company.email',       'label'=>'Email de contact',        'type'=>'email',   'default_value'=>'',                      'sort_order'=>9],
            ['group'=>'company','key'=>'company.phone',       'label'=>'Téléphone',               'type'=>'text',    'default_value'=>'',                      'sort_order'=>10],
            ['group'=>'company','key'=>'company.website',     'label'=>'Site web',                'type'=>'url',     'default_value'=>'',                      'sort_order'=>11],
            ['group'=>'company','key'=>'company.logo',        'label'=>'Logo (URL ou chemin)',    'type'=>'image',   'default_value'=>'',                      'sort_order'=>12],
            ['group'=>'company','key'=>'company.capital',     'label'=>'Capital social',          'type'=>'text',    'default_value'=>'',                      'sort_order'=>13],
            ['group'=>'company','key'=>'company.rcs',         'label'=>'RCS',                     'type'=>'text',    'default_value'=>'',                      'sort_order'=>14],
            ['group'=>'company','key'=>'company.naf',         'label'=>'Code NAF/APE',            'type'=>'text',    'default_value'=>'',                      'sort_order'=>15],

            // ── Facture PDF ────────────────────────────────────────────────────
            ['group'=>'invoice','key'=>'invoice.primary_color',      'label'=>'Couleur principale',          'type'=>'color',    'default_value'=>'#1a1a1a',   'sort_order'=>1],
            ['group'=>'invoice','key'=>'invoice.secondary_color',    'label'=>'Couleur secondaire',          'type'=>'color',    'default_value'=>'#6b7280',   'sort_order'=>2],
            ['group'=>'invoice','key'=>'invoice.header_title',       'label'=>'Titre en-tête',              'type'=>'text',     'default_value'=>'Facture',    'sort_order'=>3],
            ['group'=>'invoice','key'=>'invoice.header_subtitle',    'label'=>'Sous-titre en-tête',         'type'=>'text',     'default_value'=>'',           'sort_order'=>4],
            ['group'=>'invoice','key'=>'invoice.show_logo',          'label'=>'Afficher le logo',           'type'=>'boolean',  'default_value'=>'1',          'sort_order'=>5],
            ['group'=>'invoice','key'=>'invoice.logo_position',      'label'=>'Position du logo',           'type'=>'select',   'default_value'=>'left',
                'options'=>['left'=>'Gauche','right'=>'Droite','center'=>'Centre'],                                                                             'sort_order'=>6],
            ['group'=>'invoice','key'=>'invoice.font_size',          'label'=>'Taille police (px)',         'type'=>'number',   'default_value'=>'11',         'sort_order'=>7],
            ['group'=>'invoice','key'=>'invoice.margin_top',         'label'=>'Marge haute (mm)',           'type'=>'number',   'default_value'=>'15',         'sort_order'=>8],
            ['group'=>'invoice','key'=>'invoice.margin_bottom',      'label'=>'Marge basse (mm)',           'type'=>'number',   'default_value'=>'15',         'sort_order'=>9],
            ['group'=>'invoice','key'=>'invoice.margin_left',        'label'=>'Marge gauche (mm)',          'type'=>'number',   'default_value'=>'15',         'sort_order'=>10],
            ['group'=>'invoice','key'=>'invoice.margin_right',       'label'=>'Marge droite (mm)',          'type'=>'number',   'default_value'=>'15',         'sort_order'=>11],
            ['group'=>'invoice','key'=>'invoice.show_shipping_line', 'label'=>'Afficher ligne frais de port','type'=>'boolean', 'default_value'=>'1',          'sort_order'=>12],
            ['group'=>'invoice','key'=>'invoice.vat_default_rate',   'label'=>'Taux TVA par défaut (%)',    'type'=>'number',   'default_value'=>'2.10',       'sort_order'=>13],
            ['group'=>'invoice','key'=>'invoice.payment_delay_days', 'label'=>'Délai de paiement (jours)', 'type'=>'number',   'default_value'=>'30',         'sort_order'=>14],
            ['group'=>'invoice','key'=>'invoice.footer_line1',       'label'=>'Pied de page — ligne 1',    'type'=>'text',     'default_value'=>'',           'sort_order'=>15],
            ['group'=>'invoice','key'=>'invoice.footer_line2',       'label'=>'Pied de page — ligne 2',    'type'=>'text',     'default_value'=>'',           'sort_order'=>16],
            ['group'=>'invoice','key'=>'invoice.footer_line3',       'label'=>'Pied de page — ligne 3',    'type'=>'text',     'default_value'=>'',           'sort_order'=>17],
            ['group'=>'invoice','key'=>'invoice.legal_mentions',     'label'=>'Mentions légales',          'type'=>'textarea', 'default_value'=>'En cas de retard de paiement, une pénalité de 3 fois le taux légal sera appliquée. Indemnité forfaitaire pour frais de recouvrement : 40 €.', 'sort_order'=>18],
            ['group'=>'invoice','key'=>'invoice.payment_conditions', 'label'=>'Conditions de règlement',  'type'=>'textarea', 'default_value'=>'Règlement à réception de facture.',                                                                                                         'sort_order'=>19],
            ['group'=>'invoice','key'=>'invoice.bank_iban',          'label'=>'IBAN (pour virement)',      'type'=>'text',     'default_value'=>'',           'sort_order'=>20],
            ['group'=>'invoice','key'=>'invoice.bank_bic',           'label'=>'BIC',                       'type'=>'text',     'default_value'=>'',           'sort_order'=>21],
            ['group'=>'invoice','key'=>'invoice.bank_name',          'label'=>'Nom de la banque',          'type'=>'text',     'default_value'=>'',           'sort_order'=>22],
            ['group'=>'invoice','key'=>'invoice.number_prefix',      'label'=>'Préfixe numérotation',      'type'=>'text',     'default_value'=>'FA',         'sort_order'=>23],

            // ── Abonnements ────────────────────────────────────────────────────
            ['group'=>'subscriptions','key'=>'sub.grace_days',         'label'=>'Jours de grâce après expiration', 'type'=>'number',  'default_value'=>'0',  'sort_order'=>1],
            ['group'=>'subscriptions','key'=>'sub.reminder_days_before','label'=>'Rappel avant expiration (jours)','type'=>'number',  'default_value'=>'30', 'sort_order'=>2],
            ['group'=>'subscriptions','key'=>'sub.auto_renew_default',  'label'=>'Renouvellement auto par défaut', 'type'=>'boolean', 'default_value'=>'0',  'sort_order'=>3],

            // ── Emails ─────────────────────────────────────────────────────────
            ['group'=>'emails','key'=>'email.from_name',    'label'=>'Nom expéditeur',       'type'=>'text',     'default_value'=>'LPAbonnements', 'sort_order'=>1],
            ['group'=>'emails','key'=>'email.from_address', 'label'=>'Email expéditeur',     'type'=>'email',    'default_value'=>'',              'sort_order'=>2],
            ['group'=>'emails','key'=>'email.signature',    'label'=>'Signature email',      'type'=>'textarea', 'default_value'=>'',              'sort_order'=>3],
            ['group'=>'emails','key'=>'email.reply_to',     'label'=>'Reply-To',             'type'=>'email',    'default_value'=>'',              'sort_order'=>4],
        ];

        foreach ($settings as $s) {
            Setting::firstOrCreate(
                ['key' => $s['key']],
                array_merge($s, ['value' => null])
            );
        }

        $this->command->info(count($settings) . ' paramètres initialisés.');
    }
}
