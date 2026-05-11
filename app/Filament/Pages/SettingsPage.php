<?php
namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class SettingsPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Paramètres';
    protected static ?string $navigationLabel = 'Paramètres';
    protected static ?string $title           = 'Paramètres de l\'application';
    protected static ?int    $navigationSort  = 99;
    protected static string  $view            = 'filament.pages.settings-page';

    // Tableau unique — statePath('data') est le pattern standard Filament
    public array $data = [];

    // ── Mapping clé DB ↔ clé formulaire ───────────────────────────────────
    // DB  : "company.name"  →  Form : "company_name"
    // DB  : "company.logo"  →  Form : "company_logo"
    private static function toFormKey(string $dbKey): string
    {
        return str_replace('.', '_', $dbKey);
    }

    private static function toDbKey(string $formKey): string
    {
        // Retrouve le bon séparateur selon le groupe
        // company_name → company.name
        // ue_sans_intracom_rate → on ne touche pas (pas de setting avec ce pattern comme formKey)
        $groups = ['company', 'invoice', 'sub', 'email'];
        foreach ($groups as $g) {
            if (str_starts_with($formKey, $g . '_')) {
                $rest = substr($formKey, strlen($g) + 1);
                return $g . '.' . $rest;
            }
        }
        return $formKey;
    }

    // ── Chargement ────────────────────────────────────────────────────────
    public function mount(): void
    {
        $all = Setting::all();
        $formData = [];

        foreach ($all as $setting) {
            $formKey = self::toFormKey($setting->key);
            $val     = $setting->value ?? $setting->default_value;

            // FileUpload attend null (pas "") sinon Livewire plante
            if ($setting->type === 'image') {
                $val = empty($val) ? null : $val;
            }

            $formData[$formKey] = $val;
        }

        $this->form->fill($formData);
    }

    // ── Formulaire ────────────────────────────────────────────────────────
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Paramètres')
                    ->tabs([

                        // ══ Société ═══════════════════════════════════════
                        Forms\Components\Tabs\Tab::make('Société')
                            ->icon('heroicon-o-building-office')
                            ->schema([

                                Forms\Components\Section::make('Identité')
                                    ->schema([
                                        Forms\Components\TextInput::make('company_name')
                                            ->label('Nom de la société')
                                            ->required(),
                                        Forms\Components\TextInput::make('company_email')
                                            ->label('Email de contact')
                                            ->email(),
                                        Forms\Components\TextInput::make('company_phone')
                                            ->label('Téléphone'),
                                        Forms\Components\TextInput::make('company_website')
                                            ->label('Site web')
                                            ->url()
                                            ->placeholder('https://'),
                                        Forms\Components\FileUpload::make('company_logo')
                                            ->label('Logo')
                                            ->image()
                                            ->imagePreviewHeight('80')
                                            ->acceptedFileTypes(['image/png','image/jpeg','image/svg+xml','image/webp'])
                                            ->maxSize(2048)
                                            ->disk('public')
                                            ->directory('settings/logos')
                                            ->visibility('public')
                                            ->columnSpanFull(),
                                    ])->columns(2),

                                Forms\Components\Section::make('Adresse')
                                    ->schema([
                                        Forms\Components\TextInput::make('company_address1')
                                            ->label('Adresse ligne 1')
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('company_address2')
                                            ->label('Adresse ligne 2')
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('company_postal_code')
                                            ->label('Code postal'),
                                        Forms\Components\TextInput::make('company_city')
                                            ->label('Ville'),
                                        Forms\Components\TextInput::make('company_country')
                                            ->label('Pays')
                                            ->default('France'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Informations légales')
                                    ->schema([
                                        Forms\Components\TextInput::make('company_siret')
                                            ->label('SIRET'),
                                        Forms\Components\TextInput::make('company_vat_number')
                                            ->label('N° TVA intracommunautaire'),
                                        Forms\Components\TextInput::make('company_rcs')
                                            ->label('RCS'),
                                        Forms\Components\TextInput::make('company_naf')
                                            ->label('Code NAF/APE'),
                                        Forms\Components\TextInput::make('company_capital')
                                            ->label('Capital social'),
                                    ])->columns(2),
                            ]),

                        // ══ Facture PDF ════════════════════════════════════
                        Forms\Components\Tabs\Tab::make('Facture PDF')
                            ->icon('heroicon-o-document-text')
                            ->schema([

                                Forms\Components\Section::make('Apparence')
                                    ->schema([
                                        Forms\Components\ColorPicker::make('invoice_primary_color')
                                            ->label('Couleur principale')
                                            ->helperText('En-têtes, bordures, total'),
                                        Forms\Components\ColorPicker::make('invoice_secondary_color')
                                            ->label('Couleur secondaire')
                                            ->helperText('Textes secondaires'),
                                        Forms\Components\TextInput::make('invoice_font_size')
                                            ->label('Taille police (px)')
                                            ->numeric()
                                            ->minValue(8)->maxValue(14),
                                        Forms\Components\Select::make('invoice_logo_position')
                                            ->label('Position du logo')
                                            ->options(['left'=>'Gauche','right'=>'Droite','center'=>'Centre']),
                                        Forms\Components\Toggle::make('invoice_show_logo')
                                            ->label('Afficher le logo'),
                                        Forms\Components\Toggle::make('invoice_show_shipping_line')
                                            ->label('Afficher ligne frais de port'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Marges (mm)')
                                    ->schema([
                                        Forms\Components\TextInput::make('invoice_margin_top')
                                            ->label('Haut')->numeric()->minValue(0)->maxValue(50),
                                        Forms\Components\TextInput::make('invoice_margin_bottom')
                                            ->label('Bas')->numeric()->minValue(0)->maxValue(50),
                                        Forms\Components\TextInput::make('invoice_margin_left')
                                            ->label('Gauche')->numeric()->minValue(0)->maxValue(50),
                                        Forms\Components\TextInput::make('invoice_margin_right')
                                            ->label('Droite')->numeric()->minValue(0)->maxValue(50),
                                    ])->columns(4),

                                Forms\Components\Section::make('Pied de page')
                                    ->schema([
                                        Forms\Components\TextInput::make('invoice_footer_line1')
                                            ->label('Ligne 1')
                                            ->placeholder('Ex: Nom Société — Adresse'),
                                        Forms\Components\TextInput::make('invoice_footer_line2')
                                            ->label('Ligne 2')
                                            ->placeholder('Ex: SIRET / TVA'),
                                        Forms\Components\TextInput::make('invoice_footer_line3')
                                            ->label('Ligne 3')
                                            ->placeholder('Ex: Email / Téléphone'),
                                    ])->columns(1),

                                Forms\Components\Section::make('Textes légaux et conditions')
                                    ->schema([
                                        Forms\Components\Textarea::make('invoice_payment_conditions')
                                            ->label('Conditions de règlement')
                                            ->rows(2),
                                        Forms\Components\Textarea::make('invoice_legal_mentions')
                                            ->label('Mentions légales')
                                            ->rows(3),
                                    ])->columns(1),

                                Forms\Components\Section::make('Coordonnées bancaires')
                                    ->description('Affichées en pied de facture si renseignées')
                                    ->schema([
                                        Forms\Components\TextInput::make('invoice_bank_name')
                                            ->label('Banque'),
                                        Forms\Components\TextInput::make('invoice_bank_iban')
                                            ->label('IBAN'),
                                        Forms\Components\TextInput::make('invoice_bank_bic')
                                            ->label('BIC'),
                                    ])->columns(3),

                                Forms\Components\Section::make('Numérotation & TVA')
                                    ->schema([
                                        Forms\Components\TextInput::make('invoice_number_prefix')
                                            ->label('Préfixe N° facture')
                                            ->helperText('Ex: FA → FA202605001')
                                            ->maxLength(10),
                                        Forms\Components\TextInput::make('invoice_vat_default_rate')
                                            ->label('Taux TVA par défaut (%)')
                                            ->numeric(),
                                        Forms\Components\TextInput::make('invoice_payment_delay_days')
                                            ->label('Délai paiement (jours)')
                                            ->numeric(),
                                    ])->columns(3),
                            ]),

                        // ══ Abonnements ════════════════════════════════════
                        Forms\Components\Tabs\Tab::make('Abonnements')
                            ->icon('heroicon-o-newspaper')
                            ->schema([
                                Forms\Components\Section::make('Gestion des expirations')
                                    ->schema([
                                        Forms\Components\TextInput::make('sub_grace_days')
                                            ->label('Jours de grâce après expiration')
                                            ->numeric()->minValue(0),
                                        Forms\Components\TextInput::make('sub_reminder_days_before')
                                            ->label('Envoyer rappel X jours avant expiration')
                                            ->numeric()->minValue(0),
                                        Forms\Components\Toggle::make('sub_auto_renew_default')
                                            ->label('Renouvellement automatique activé par défaut'),
                                    ])->columns(2),
                            ]),

                        // ══ Emails ══════════════════════════════════════════
                        Forms\Components\Tabs\Tab::make('Emails')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Forms\Components\Section::make('Expéditeur')
                                    ->schema([
                                        Forms\Components\TextInput::make('email_from_name')
                                            ->label('Nom affiché'),
                                        Forms\Components\TextInput::make('email_from_address')
                                            ->label('Adresse email')
                                            ->email(),
                                        Forms\Components\TextInput::make('email_reply_to')
                                            ->label('Reply-To')
                                            ->email(),
                                    ])->columns(2),
                                Forms\Components\Section::make('Contenu')
                                    ->schema([
                                        Forms\Components\Textarea::make('email_signature')
                                            ->label('Signature')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    // ── Sauvegarde ────────────────────────────────────────────────────────
    public function save(): void
    {
        $formData = $this->form->getState();

        foreach ($formData as $formKey => $value) {
            $dbKey = self::toDbKey($formKey);

            // FileUpload retourne un tableau ['chemin'] ou null
            if (is_array($value)) {
                $value = !empty($value) ? array_values($value)[0] : null;
            }

            // Ne pas écraser le logo existant si aucun nouveau fichier
            if ($formKey === 'company_logo' && $value === null) {
                continue;
            }

            $updated = Setting::where('key', $dbKey)->update(['value' => $value]);

            // Si la clé n'existe pas encore en base, on la crée
            if ($updated === 0) {
                Setting::updateOrCreate(
                    ['key' => $dbKey],
                    ['value' => $value, 'group' => explode('.', $dbKey)[0]]
                );
            }
        }

        // Invalider tout le cache settings
        Setting::all()->each(fn($s) => Cache::forget("setting.{$s->key}"));
        foreach (['company', 'invoice', 'subscriptions', 'emails', 'sub', 'email'] as $g) {
            Cache::forget("settings.group.{$g}");
        }

        Notification::make()
            ->title('Paramètres enregistrés')
            ->success()
            ->send();
    }
}
