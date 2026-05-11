<?php

namespace App\Filament\Resources;

use App\Enums\ClientStatus;
use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use App\Services\SireneService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Clients';
    protected static ?string $modelLabel = 'Client';
    protected static ?string $pluralModelLabel = 'Clients';
    protected static ?string $navigationGroup = 'Clients';
    protected static ?int $navigationSort = 1;

    // ══════════════════════════════════════════════════════════
    // FORMULAIRE
    // ══════════════════════════════════════════════════════════

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()
                ->tabs([

                    // ── ONGLET 1 : IDENTITÉ ───────────────────────────────
                    Forms\Components\Tabs\Tab::make('Identité')
                        ->icon('heroicon-o-user')
                        ->schema([

                            Forms\Components\Section::make('Type de client')
                                ->schema([
                                    Forms\Components\Grid::make(12)->schema([

                                        Forms\Components\Select::make('type')
                                            ->label('Type')
                                            ->options([
                                                'individual' => 'Particulier',
                                                'company'    => 'Entreprise',
                                            ])
                                            ->required()
                                            ->live()
                                            ->columnSpan(2),

                                        Forms\Components\Select::make('civility')
                                            ->label('Civilité')
                                            ->options([
                                                'M'   => 'M.',
                                                'Mme' => 'Mme',
                                                'Dr'  => 'Dr',
                                                'Pr'  => 'Pr',
                                            ])
                                            ->columnSpan(2)
                                            ->visible(fn ($get) => $get('type') === 'individual'),

                                        Forms\Components\TextInput::make('first_name')
                                            ->label('Prénom')
                                            ->columnSpan(3)
                                            ->visible(fn ($get) => $get('type') === 'individual'),

                                        Forms\Components\TextInput::make('last_name')
                                            ->label('Nom')
                                            ->required(fn ($get) => $get('type') === 'individual')
                                            ->columnSpan(3)
                                            ->visible(fn ($get) => $get('type') === 'individual'),

                                        Forms\Components\TextInput::make('company_name')
                                            ->label('Raison sociale')
                                            ->required(fn ($get) => $get('type') === 'company')
                                            ->columnSpan(8)
                                            ->visible(fn ($get) => $get('type') === 'company'),

                                        Forms\Components\TextInput::make('client_number')
                                            ->label('N° client')
                                            ->disabled()
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('external_code')
                                            ->label('Code externe')
                                            ->columnSpan(2),
                                    ]),
                                ])
                                ->compact(),

                            Forms\Components\Section::make('Identification fiscale')
                                ->schema([
                                    Forms\Components\Grid::make(12)->schema([

                                        Forms\Components\TextInput::make('siret')
                                            ->label('SIRET')
                                            ->columnSpan(4)
                                            ->suffixAction(
                                                Forms\Components\Actions\Action::make('searchSiret')
                                                    ->icon('heroicon-o-magnifying-glass')
                                                    ->modalHeading('Recherche SIRET / SIREN')
                                                    ->modalSubmitActionLabel('Appliquer')
                                                    ->modalWidth('lg')
                                                    ->form([
                                                        Forms\Components\Placeholder::make('ph_company')
                                                            ->label('Entreprise trouvée')
                                                            ->content(fn (Forms\Get $get): string => $get('_company_name') ?: '—'),
                                                        Forms\Components\Placeholder::make('ph_siret')
                                                            ->label('SIRET')
                                                            ->content(fn (Forms\Get $get): string => $get('_siret') ?: '—'),
                                                        Forms\Components\Placeholder::make('ph_vat')
                                                            ->label('N° TVA intracommunautaire')
                                                            ->content(fn (Forms\Get $get): string => $get('_vat_number') ?: '—'),
                                                        Forms\Components\Placeholder::make('ph_address')
                                                            ->label('Adresse du siège social')
                                                            ->content(fn (Forms\Get $get): string =>
                                                                collect([
                                                                    $get('_address_line'),
                                                                    trim(($get('_postal_code') ?: '') . ' ' . ($get('_city') ?: '')),
                                                                ])->filter()->implode(', ') ?: '—'
                                                            ),
                                                        Forms\Components\Toggle::make('update_address')
                                                            ->label('Mettre à jour l\'adresse de facturation')
                                                            ->helperText('Crée ou met à jour l\'adresse par défaut avec l\'adresse du siège social')
                                                            ->default(true),
                                                        Forms\Components\Hidden::make('_company_name'),
                                                        Forms\Components\Hidden::make('_siret'),
                                                        Forms\Components\Hidden::make('_vat_number'),
                                                        Forms\Components\Hidden::make('_address_line'),
                                                        Forms\Components\Hidden::make('_postal_code'),
                                                        Forms\Components\Hidden::make('_city'),
                                                    ])
                                                    ->fillForm(function ($state): array {
                                                        if (blank($state)) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Saisissez un SIRET ou SIREN')
                                                                ->warning()->send();
                                                            return [];
                                                        }
                                                        $data = app(\App\Services\SireneService::class)->lookup($state);
                                                        if (!$data) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Introuvable')
                                                                ->body('Aucune entreprise trouvée pour ce numéro.')
                                                                ->warning()->send();
                                                            return [];
                                                        }
                                                        return [
                                                            '_company_name'  => $data['company_name'] ?? '',
                                                            '_siret'         => $data['siret']        ?? '',
                                                            '_vat_number'    => $data['vat_number']   ?? '',
                                                            '_address_line'  => $data['_sirene_l4']          ?? '',
                                                            '_postal_code'   => $data['_sirene_postal_code'] ?? '',
                                                            '_city'          => $data['_sirene_city']        ?? '',
                                                            'update_address' => true,
                                                        ];
                                                    })
                                                    ->action(function (array $data, $set, $record): void {
                                                        if (blank($data['_company_name'] ?? null)) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Aucune donnée à appliquer')
                                                                ->warning()->send();
                                                            return;
                                                        }
                                                        $set('company_name', $data['_company_name']);
                                                        $set('siret',        $data['_siret']);
                                                        $set('vat_number',   $data['_vat_number']);
                                                        if (($data['update_address'] ?? false) && $record?->id) {
                                                            $addressData = [
                                                                'address_type'   => 'entreprise',
                                                                'usage'          => 'both',
                                                                'is_default'     => true,
                                                                'l1'             => $data['_company_name'],
                                                                'l4'             => $data['_address_line'],
                                                                'l6_postal_code' => $data['_postal_code'],
                                                                'l6_city'        => mb_strtoupper($data['_city']),
                                                                'l7_country'     => 'FR',
                                                            ];
                                                            $existing = $record->addresses()
                                                                ->where('is_default', true)
                                                                ->first();
                                                            if ($existing) {
                                                                $existing->update($addressData);
                                                            } else {
                                                                $addressData['name'] = 'Adresse principale';
                                                                $record->addresses()->create($addressData);
                                                            }
                                                        }
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Fiche mise à jour')
                                                            ->body($data['_company_name'] . (($data['update_address'] ?? false) ? ' — adresse mise à jour' : ''))
                                                            ->success()->send();
                                                    })
                                            ),

                                        Forms\Components\TextInput::make('vat_number')
                                            ->label('N° TVA intracom.')
                                            ->columnSpan(4),

                                        Forms\Components\TextInput::make('company_email')
                                            ->label('Email entreprise')
                                            ->email()
                                            ->columnSpan(4),
                                    ]),
                                ])
                                ->compact()
                                ->visible(fn ($get) => $get('type') === 'company'),

                            Forms\Components\Section::make('Contact')
                                ->schema([
                                    Forms\Components\Grid::make(12)->schema([

                                        Forms\Components\TextInput::make('email')
                                            ->label('Email contact')
                                            ->email()
                                            ->required()
                                            ->columnSpan(4)
                                            ->suffixAction(
                                                Forms\Components\Actions\Action::make('sendWelcome')
                                                    ->icon('heroicon-o-envelope')
                                                    ->tooltip('Envoyer email de bienvenue / accès portail')
                                                    ->color('info')
                                                    ->requiresConfirmation()
                                                    ->modalHeading('Envoyer un email au client')
                                                    ->modalDescription('Si le compte n\'est pas encore activé : email de bienvenue avec lien de création de mot de passe. Si le compte est actif : email de notification d\'accès au portail.')
                                                    ->modalSubmitActionLabel('Envoyer')
                                                    ->action(function ($record) {
                                                        if (!$record) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Enregistrez d\'abord le client')
                                                                ->warning()->send();
                                                            return;
                                                        }
                                                        try {
                                                            (new \App\Services\ClientAccountService())->handleAccountForClient($record);
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Email envoyé avec succès')
                                                                ->success()->send();
                                                        } catch (\Throwable $e) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Erreur : ' . $e->getMessage())
                                                                ->danger()->send();
                                                        }
                                                    })
                                                    ->visible(fn ($record) => $record !== null && $record->email)
                                            ),

                                        Forms\Components\TextInput::make('phone')
                                            ->label('Tél principal')
                                            ->tel()
                                            ->columnSpan(3),

                                        Forms\Components\TextInput::make('mobile')
                                            ->label('Tél secondaire')
                                            ->tel()
                                            ->columnSpan(3),
                                    ]),
                                ])
                                ->compact(),

                            Forms\Components\Section::make('Statut')
                                ->schema([
                                    Forms\Components\Grid::make(12)->schema([

                                        Forms\Components\Select::make('status')
                                            ->label('Statut')
                                            ->options(ClientStatus::class)
                                            ->required()
                                            ->columnSpan(2),

                                        Forms\Components\Toggle::make('is_payer')
                                            ->label('Compte payeur')
                                            ->live()
                                            ->columnSpan(2),

                                        Forms\Components\Select::make('payer_client_id')
                                            ->label('Payeur rattaché')
                                            ->options(fn () => Client::where('is_payer', true)
                                                ->orderBy('company_name')
                                                ->orderBy('last_name')
                                                ->get()
                                                ->mapWithKeys(fn ($c) => [
                                                    $c->id => $c->company_name ?: trim($c->first_name . ' ' . $c->last_name),
                                                ]))
                                            ->searchable()
                                            ->nullable()
                                            ->columnSpan(4)
                                            ->visible(fn ($get) => ! $get('is_payer')),

                                        Forms\Components\DateTimePicker::make('archived_at')
                                            ->label('Archivé le')
                                            ->disabled()
                                            ->columnSpan(2),
                                    ]),
                                ])
                                ->compact(),
                        ]),

                    // ── ONGLET 2 : ADRESSES ───────────────────────────────
                    Forms\Components\Tabs\Tab::make('Adresses')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            Forms\Components\View::make('filament.client-addresses'),
                        ]),

                    // ── ONGLET 3 : COMMANDES ──────────────────────────────
                    Forms\Components\Tabs\Tab::make('Commandes')
                        ->icon('heroicon-o-shopping-cart')
                        ->schema([
                            Forms\Components\View::make('filament.client-orders'),
                        ]),

                    // ── ONGLET 4 : ABONNEMENTS ────────────────────────────
                    Forms\Components\Tabs\Tab::make('Abonnements')
                        ->icon('heroicon-o-newspaper')
                        ->schema([
                            Forms\Components\View::make('filament.client-subscriptions')->viewData(fn (\Livewire\Component $livewire) => ['record' => $livewire->getRecord()]),
                        ]),

                    // ── ONGLET 5 : COMPTABILITÉ ───────────────────────────
                    Forms\Components\Tabs\Tab::make('Comptabilité')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Forms\Components\View::make('filament.client-invoices'),
                            Forms\Components\View::make('filament.client-payments'),
                        ]),

                    // ── ONGLET 6 : INFOS COMPLÉMENTAIRES ─────────────────
                    Forms\Components\Tabs\Tab::make('Infos complémentaires')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema(static::buildCustomFieldsSchema()),

                    // ── ONGLET 7 : NOTES & STATUT ─────────────────────────
                    Forms\Components\Tabs\Tab::make('Notes & statut')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->schema([
                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\Textarea::make('notes')
                                        ->label('Notes internes')
                                        ->rows(6)
                                        ->columnSpanFull(),
                                    Forms\Components\Grid::make(4)->schema([
                                        Forms\Components\DateTimePicker::make('created_at')
                                            ->label('Créé le')
                                            ->disabled(),
                                        Forms\Components\DateTimePicker::make('updated_at')
                                            ->label('Modifié le')
                                            ->disabled(),
                                    ]),
                                ])
                                ->compact(),
                        ]),

                ])
                ->columnSpanFull()
                ->persistTabInQueryString(),
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // LISTE
    // ══════════════════════════════════════════════════════════

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client_number')
                    ->label('N°')
                    ->sortable()
                    ->searchable()
                    ->width('80px'),

                Tables\Columns\TextColumn::make('last_name')
                    ->label('Client')
                    ->sortable()
                    ->searchable(['first_name', 'last_name', 'company_name'])
                    ->formatStateUsing(fn ($record) => $record->full_name),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => $state === 'company' ? 'Entreprise' : 'Particulier')
                    ->badge()
                    ->color(fn ($state) => $state === 'company' ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state) => (is_string($state) ? ClientStatus::from($state) : $state)->label())
                    ->color(fn (mixed $state): string => match (is_string($state) ? $state : $state->value) {
                        'active'   => 'success',
                        'inactive' => 'warning',
                        'archived' => 'danger',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_name')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ClientStatus::class),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'individual' => 'Particulier',
                        'company'    => 'Entreprise',
                    ]),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('import')
                    ->label('Importer')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->url(fn () => \App\Filament\Pages\ImportClients::getUrl()),
            ]);
    }

    // ══════════════════════════════════════════════════════════
    // CHAMPS PERSONNALISÉS
    // ══════════════════════════════════════════════════════════

    public static function buildCustomFieldsSchema(): array
    {
        $definitions = \App\Models\CustomFieldDefinition::where('is_active', true)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        if ($definitions->isEmpty()) {
            return [
                Forms\Components\Placeholder::make('no_custom_fields')
                    ->label('')
                    ->content('Aucun champ personnalisé défini. Rendez-vous dans Paramètres > Champs personnalisés.'),
            ];
        }

        $grouped = $definitions->groupBy('group');
        $schema  = [];

        foreach ($grouped as $group => $fields) {
            $fieldComponents = [];

            foreach ($fields as $def) {
                $fieldComponents[] = match ($def->type) {
                    'text'     => Forms\Components\TextInput::make("custom_fields.{$def->slug}")
                                    ->label($def->label)->required($def->required)
                                    ->default($def->default_value)->helperText($def->description),
                    'textarea' => Forms\Components\Textarea::make("custom_fields.{$def->slug}")
                                    ->label($def->label)->required($def->required)
                                    ->rows(3)->helperText($def->description),
                    'number'   => Forms\Components\TextInput::make("custom_fields.{$def->slug}")
                                    ->label($def->label)->numeric()->required($def->required)
                                    ->default($def->default_value)->helperText($def->description),
                    'date'     => Forms\Components\DatePicker::make("custom_fields.{$def->slug}")
                                    ->label($def->label)->required($def->required)
                                    ->helperText($def->description),
                    'select'   => Forms\Components\Select::make("custom_fields.{$def->slug}")
                                    ->label($def->label)->options($def->options ?? [])
                                    ->required($def->required)->helperText($def->description),
                    'boolean'  => Forms\Components\Toggle::make("custom_fields.{$def->slug}")
                                    ->label($def->label)->helperText($def->description),
                    'email'    => Forms\Components\TextInput::make("custom_fields.{$def->slug}")
                                    ->label($def->label)->email()->required($def->required)
                                    ->helperText($def->description),
                    'url'      => Forms\Components\TextInput::make("custom_fields.{$def->slug}")
                                    ->label($def->label)->url()->required($def->required)
                                    ->helperText($def->description),
                    'phone'    => Forms\Components\TextInput::make("custom_fields.{$def->slug}")
                                    ->label($def->label)->tel()->required($def->required)
                                    ->helperText($def->description),
                    default    => Forms\Components\TextInput::make("custom_fields.{$def->slug}")
                                    ->label($def->label),
                };
            }

            $schema[] = Forms\Components\Section::make($group ?: 'Général')
                ->schema([Forms\Components\Grid::make(3)->schema($fieldComponents)])
                ->compact()
                ->collapsible();
        }

        return $schema;
    }

    // ══════════════════════════════════════════════════════════
    // PAGES
    // ══════════════════════════════════════════════════════════

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit'   => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount(['subscriptions']);
    }
}
