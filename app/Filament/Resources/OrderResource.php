<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Magazine;
use App\Models\Order;
use App\Models\SubscriptionPlan;
use App\Services\OrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model            = Order::class;
    protected static ?string $navigationIcon   = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup  = 'Abonnements';
    protected static ?string $navigationLabel  = 'Commandes';
    protected static ?string $modelLabel       = 'Commande';
    protected static ?string $pluralModelLabel = 'Commandes';
    protected static ?int    $navigationSort   = 5;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function clientCard(Client $client): string
    {
        $lines = array_filter([
            '<strong>' . e($client->display_name) . '</strong>'
                . ($client->client_number ? ' <span class="text-gray-400">[' . e($client->client_number) . ']</span>' : ''),
            $client->company_name && $client->type !== 'company'
                ? e($client->company_name) : null,
            $client->email  ? '📧 ' . e($client->email)  : null,
            $client->phone  ? '📞 ' . e($client->phone)  : null,
            $client->mobile ? '📱 ' . e($client->mobile) : null,
            $client->siret  ? 'SIRET : ' . e($client->siret) : null,
        ]);
        return implode('<br>', $lines);
    }

    private static function addressBlock(Client $client, bool $delivery = false): string
    {
        $addr = $delivery
            ? ($client->default_delivery_address ?? $client->default_billing_address)
            : $client->default_billing_address;

        if (! $addr) {
            return '<em class="text-gray-400">Non renseignée</em>';
        }

        $lines = array_filter([
            $addr->l1,
            $addr->l2,
            $addr->l3,
            $addr->l4,
            $addr->l5,
            trim(($addr->l6_postal_code ?? '') . ' ' . strtoupper($addr->l6_city ?? '') . ($addr->l6_cedex ? ' CEDEX ' . $addr->l6_cedex : '')),
            $addr->l7_country !== 'FR' ? $addr->l7_country : null,
        ]);

        return implode('<br>', array_map('e', $lines)) ?: '<em class="text-gray-400">Non renseignée</em>';
    }

    private static function planLabel(SubscriptionPlan $p): string
    {
        $supportVal = is_string($p->support_type) ? $p->support_type : $p->support_type->value;
        $support = match ($supportVal) {
            'paper'    => 'Papier',
            'digital'  => 'Numérique',
            'combined' => 'Combiné',
            default    => $supportVal,
        };
        $modeVal = is_string($p->mode) ? $p->mode : $p->mode->value;
        $duree = $modeVal === 'duration'
            ? ($p->duration_months . ' mois')
            : ($p->issues_count . ' N°');
        return $p->name
            . ' (' . $support . ' — ' . $duree . ')'
            . ' — ' . number_format($p->price, 2, ',', ' ') . ' €';
    }

    // ── Formulaire ────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Hidden::make('client_id'),
            Forms\Components\Hidden::make('beneficiary_id'),
            Forms\Components\Hidden::make('beneficiary_ids'),
            Forms\Components\Hidden::make('billing_name'),
            Forms\Components\Hidden::make('billing_address1'),
            Forms\Components\Hidden::make('billing_address2'),
            Forms\Components\Hidden::make('billing_address3'),
            Forms\Components\Hidden::make('billing_postal_code'),
            Forms\Components\Hidden::make('billing_city'),
            Forms\Components\Hidden::make('billing_cedex'),
            Forms\Components\Hidden::make('billing_country'),
            Forms\Components\Hidden::make('delivery_company'),
            Forms\Components\Hidden::make('delivery_recipient'),
            Forms\Components\Hidden::make('delivery_address1'),
            Forms\Components\Hidden::make('delivery_address2'),
            Forms\Components\Hidden::make('delivery_address3'),
            Forms\Components\Hidden::make('delivery_postal_code'),
            Forms\Components\Hidden::make('delivery_city'),
            Forms\Components\Hidden::make('delivery_cedex'),
            Forms\Components\Hidden::make('delivery_country'),

            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\TextInput::make('number')
                        ->label('N° commande')
                        ->disabled()
                        ->placeholder('Généré automatiquement'),
                    Forms\Components\Placeholder::make('status_label')
                        ->label('Statut')
                        ->content(fn($record) => $record
                            ? (is_string($record->status)
                                ? OrderStatus::from($record->status)
                                : $record->status)->label()
                            : 'Brouillon'),
                    Forms\Components\DatePicker::make('order_date')
                        ->label('Date')
                        ->default(now())
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(3),

            Forms\Components\Tabs::make('Onglets')->tabs([

                Forms\Components\Tabs\Tab::make('Client')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Section::make('Client payeur')
                            ->schema([
                                Forms\Components\Placeholder::make('payer_card')
                                    ->label('')
                                    ->content(function ($record, Get $get) {
                                        $id = $record?->client_id ?? $get('client_id');
                                        if (! $id) return '—';
                                        $c = Client::find($id);
                                        return $c
                                            ? new \Illuminate\Support\HtmlString(self::clientCard($c))
                                            : '—';
                                    }),
                            ])
                            ->columnSpan(1),

                        Forms\Components\Section::make('Bénéficiaire(s)')
                            ->schema([
                                Forms\Components\Placeholder::make('beneficiary_card')
                                    ->label('')
                                    ->content(function ($record, Get $get) {
                                        $ids = $record?->beneficiary_ids ?? $get('beneficiary_ids');
                                        if (! empty($ids)) {
                                            $clients = \App\Models\Client::whereIn('id', $ids)->get();
                                            if ($clients->isEmpty()) return '—';
                                            $html = $clients->map(fn($c) =>
                                                '<div class="mb-2 pb-2 border-b border-gray-200 dark:border-gray-700 last:border-0">'
                                                . self::clientCard($c)
                                                . '</div>'
                                            )->implode('');
                                            return new \Illuminate\Support\HtmlString($html);
                                        }
                                        $id = $record?->beneficiary_id ?? $get('beneficiary_id');
                                        if (! $id) return new \Illuminate\Support\HtmlString(
                                            '<span class="text-gray-400 italic">Identique au payeur</span>'
                                        );
                                        $c = \App\Models\Client::find($id);
                                        return $c
                                            ? new \Illuminate\Support\HtmlString(self::clientCard($c))
                                            : '—';
                                    }),
                            ])
                            ->columnSpan(1),

                    ])->columns(2),

                Forms\Components\Tabs\Tab::make('Adresses')
                    ->icon('heroicon-o-map-pin')
                    ->schema([

                        Forms\Components\Section::make('Adresse de facturation')
                            ->schema([
                                Forms\Components\Placeholder::make('billing_display')
                                    ->label('')
                                    ->content(function ($record, Get $get) {
                                        $id = $record?->client_id ?? $get('client_id');
                                        if (! $id) return '—';
                                        $c = Client::find($id);
                                        return $c
                                            ? new \Illuminate\Support\HtmlString(self::addressBlock($c, false))
                                            : '—';
                                    }),
                            ])
                            ->columnSpan(1),

                        Forms\Components\Section::make('Adresse de livraison')
                            ->schema([
                                Forms\Components\Placeholder::make('delivery_display')
                                    ->label('')
                                    ->content(function ($record, Get $get) {
                                        $benId = $record?->beneficiary_id ?? $get('beneficiary_id');
                                        $payId = $record?->client_id     ?? $get('client_id');
                                        $id    = $benId ?: $payId;
                                        if (! $id) return '—';
                                        $c = Client::find($id);
                                        return $c
                                            ? new \Illuminate\Support\HtmlString(self::addressBlock($c, true))
                                            : '—';
                                    }),
                            ])
                            ->columnSpan(1),

                        Forms\Components\Section::make('Modifier l\'adresse de livraison')
                            ->schema([
                                Forms\Components\Toggle::make('use_different_delivery')
                                    ->label('Saisir une adresse de livraison différente')
                                    ->default(false)
                                    ->live()
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('delivery_company')
                                    ->label('Société')->maxLength(38)->columnSpan(2),
                                Forms\Components\TextInput::make('delivery_recipient')
                                    ->label('Destinataire')->maxLength(38)->columnSpan(2),
                                Forms\Components\TextInput::make('delivery_address1')
                                    ->label('N° et voie')->maxLength(38)->columnSpan(2),
                                Forms\Components\TextInput::make('delivery_address2')
                                    ->label('Complément')->maxLength(38)->columnSpan(2),
                                Forms\Components\TextInput::make('delivery_address3')
                                    ->label('Lieu-dit / BP')->maxLength(38)->columnSpan(2),
                                Forms\Components\TextInput::make('delivery_postal_code')
                                    ->label('CP')->maxLength(10),
                                Forms\Components\TextInput::make('delivery_city')
                                    ->label('Ville')->maxLength(50),
                                Forms\Components\TextInput::make('delivery_cedex')
                                    ->label('CEDEX')->maxLength(20),
                                Forms\Components\Select::make('delivery_country')
                                    ->label('Pays')
                                    ->options(['FR' => 'France', 'BE' => 'Belgique',
                                               'CH' => 'Suisse', 'LU' => 'Luxembourg'])
                                    ->default('FR'),
                                Forms\Components\Toggle::make('save_delivery_to_client')
                                    ->label('Enregistrer sur la fiche client')
                                    ->default(false)
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->visible(fn(Get $get) => (bool) $get('use_different_delivery'))
                            ->columnSpanFull(),

                    ])->columns(2),

                Forms\Components\Tabs\Tab::make('Publications & Formules')
                    ->icon('heroicon-o-newspaper')
                    ->schema([

                        TableRepeater::make('lines')
                            ->relationship('lines')
                            ->headers([
                                Header::make('Publication')->width('20%'),
                                Header::make('Formule')->width('25%'),
                                Header::make('Début')->width('12%'),
                                Header::make('Fin')->width('12%'),
                                Header::make('Nb N°')->width('8%'),
                                Header::make('Support')->width('10%'),
                                Header::make('Prix TTC')->width('10%'),
                            ])
                            ->schema([
                                Forms\Components\Select::make('magazine_id')
                                    ->label('Publication')
                                    ->options(
                                        Magazine::where('is_active', true)
                                            ->orderBy('sort_order')
                                            ->pluck('name', 'id')
                                    )
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('subscription_plan_id', null);
                                        $set('unit_price', null);
                                        $set('support', null);
                                        $set('end_date', null);
                                        $set('issues_count', null);
                                    })
                                    ->columnSpan(3),

                                Forms\Components\Select::make('subscription_plan_id')
                                    ->label('Formule')
                                    ->options(fn(Get $get) => $get('magazine_id')
                                        ? SubscriptionPlan::where('magazine_id', $get('magazine_id'))
                                            ->where('is_active', true)
                                            ->orderBy('sort_order')
                                            ->get()
                                            ->mapWithKeys(fn($p) => [$p->id => self::planLabel($p)])
                                        : [])
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if (! $state) return;
                                        $plan = SubscriptionPlan::find($state);
                                        if (! $plan) return;
                                        $set('unit_price', $plan->price);
                                        $set('support', match(is_string($plan->support_type) ? $plan->support_type : $plan->support_type->value) {
                                            'paper'    => 'Papier',
                                            'digital'  => 'Numérique',
                                            'combined' => 'Combiné',
                                            default    => (is_string($plan->support_type) ? $plan->support_type : $plan->support_type->value),
                                        });
                                        $start = $get('start_date') ?: now()->format('Y-m-d');
                                        $modeV = is_string($plan->mode) ? $plan->mode : $plan->mode->value;
                                        if (! $get('start_date')) {
                                            $set('start_date', now()->format('Y-m-d'));
                                            $start = now()->format('Y-m-d');
                                        }
                                        if ($modeV === 'duration' && ($plan->duration_months ?? 0) > 0) {
                                            $set('end_date', now()->parse($start)
                                                ->addMonths($plan->duration_months)
                                                ->subDay()->format('Y-m-d'));
                                            $set('issues_count', null);
                                        } elseif ($modeV === 'issues') {
                                            $set('issues_count', $plan->issues_count);
                                            $set('end_date', null);
                                        }
                                    })
                                    ->columnSpan(3),

                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Début')
                                    ->default(fn() => now()->format('Y-m-d'))
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $planId = $get('subscription_plan_id');
                                        if (! $planId || ! $state) return;
                                        $plan     = SubscriptionPlan::find($planId);
                                        $planMode = is_string($plan->mode) ? $plan->mode : $plan->mode->value;
                                        if ($plan && $planMode === 'duration'
                                            && ($plan->duration_months ?? 0) > 0) {
                                            $set('end_date', now()->parse($state)
                                                ->addMonths($plan->duration_months)
                                                ->subDay()->format('Y-m-d'));
                                        }
                                    })
                                    ->columnSpan(2),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Fin')
                                    ->nullable()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('issues_count')
                                    ->label('Nb N°')
                                    ->numeric()->nullable()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('support')
                                    ->label('Support')
                                    ->disabled()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Prix TTC')
                                    ->numeric()->required()
                                    ->minValue(0)
                                    ->suffix('€')
                                    ->columnSpan(1),
                            ])
                            ->addActionLabel('+ Ajouter une publication')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)->schema([

                            Forms\Components\Section::make('Remise')
                                ->schema([
                                    Forms\Components\TextInput::make('discount_percent')
                                        ->label('Remise (%)')
                                        ->numeric()->default(0)->minValue(0)->maxValue(100)
                                        ->suffix('%')
                                        ->live()
                                        ->afterStateUpdated(fn(Set $set) => $set('discount_amount', 0)),
                                    Forms\Components\TextInput::make('discount_amount')
                                        ->label('Remise (€)')
                                        ->numeric()->default(0)->minValue(0)
                                        ->suffix('€')
                                        ->live()
                                        ->afterStateUpdated(fn(Set $set) => $set('discount_percent', 0)),
                                ])
                                ->columnSpan(1),

                            Forms\Components\Section::make('Totaux')
                                ->schema([
                                    Forms\Components\Placeholder::make('total_ht_display')
                                        ->label('Montant HT')
                                        ->content(function (Get $get) {
                                            $lines = $get('lines') ?? [];
                                            $ttc   = array_sum(array_column($lines, 'unit_price'));
                                            $tva   = round($ttc * 2.10 / 102.10, 2);
                                            $ht    = $ttc - $tva;
                                            $disc  = (float)($get('discount_percent') ?? 0) > 0
                                                ? round($ttc * (float)$get('discount_percent') / 100, 2)
                                                : (float)($get('discount_amount') ?? 0);
                                            $htNet = max(0, $ht - round($disc * $ht / max($ttc, 0.01), 2));
                                            return number_format($htNet, 2, ',', ' ') . ' €';
                                        }),
                                    Forms\Components\Placeholder::make('total_tva_display')
                                        ->label('dont TVA (2,10%)')
                                        ->content(function (Get $get) {
                                            $lines  = $get('lines') ?? [];
                                            $ttc    = array_sum(array_column($lines, 'unit_price'));
                                            $tva    = round($ttc * 2.10 / 102.10, 2);
                                            $disc   = (float)($get('discount_percent') ?? 0) > 0
                                                ? round($ttc * (float)$get('discount_percent') / 100, 2)
                                                : (float)($get('discount_amount') ?? 0);
                                            $tvaNet = $ttc > 0 ? round($tva * (1 - $disc / $ttc), 2) : 0;
                                            return number_format($tvaNet, 2, ',', ' ') . ' €';
                                        }),
                                    Forms\Components\Placeholder::make('total_ttc_display')
                                        ->label('Total TTC')
                                        ->content(function (Get $get) {
                                            $lines = $get('lines') ?? [];
                                            $ttc   = array_sum(array_column($lines, 'unit_price'));
                                            $disc  = (float)($get('discount_percent') ?? 0) > 0
                                                ? round($ttc * (float)$get('discount_percent') / 100, 2)
                                                : (float)($get('discount_amount') ?? 0);
                                            $net   = max(0, $ttc - $disc);
                                            return new \Illuminate\Support\HtmlString(
                                                '<span class="font-bold text-xl text-primary-600">'
                                                . number_format($net, 2, ',', ' ') . ' €</span>'
                                            );
                                        }),
                                ])
                                ->columns(1)
                                ->columnSpan(1),
                        ]),
                    ]),

            ])->columnSpanFull(),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('N° Commande')
                    ->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('client.display_name')
                    ->label('Client payeur')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('client', fn($q) => $q
                            ->where('company_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                        );
                    })
                    ->sortable(query: fn(Builder $query, string $direction) => $query->orderBy(
                        \App\Models\Client::select('last_name')->whereColumn('clients.id', 'orders.client_id'), $direction
                    )),
                Tables\Columns\TextColumn::make('beneficiary.display_name')
                    ->label('Bénéficiaire')
                    ->placeholder('—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('beneficiary', fn($q) => $q
                            ->where('company_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                        );
                    }),
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Date')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('lines_count')
                    ->label('Lignes')->counts('lines')->badge(),
                Tables\Columns\TextColumn::make('total_ttc')
                    ->label('Total TTC')->money('EUR')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')->badge()
                    ->color(fn($state) => (is_string($state)
                        ? OrderStatus::from($state) : $state)->color())
                    ->formatStateUsing(fn($state) => (is_string($state)
                        ? OrderStatus::from($state) : $state)->label()),
                Tables\Columns\IconColumn::make('invoice_id')
                    ->label('Facturé')->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'brouillon' => 'Brouillon',
                        'validee'   => 'Validée',
                        'installee' => 'Installée',
                        'annulee'   => 'Annulée',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('valider')
                    ->label('Valider')->icon('heroicon-o-check-circle')->color('warning')
                    ->visible(fn(Order $r) => $r->isDraft())
                    ->requiresConfirmation()
                    ->modalHeading('Valider la commande ?')
                    ->action(function (Order $record) {
                        try {
                            app(OrderService::class)->validate($record);
                            Notification::make()->title('Commande validée')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\Action::make('installer')
                    ->label('Installer')->icon('heroicon-o-star')->color('success')
                    ->visible(fn(Order $r) => $r->isValidee())
                    ->requiresConfirmation()
                    ->modalHeading('Installer la commande ?')
                    ->action(function (Order $record) {
                        try {
                            app(OrderService::class)->install($record);
                            Notification::make()->title('Commande installée')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                // ── Facturer ──────────────────────────────────────────────────
                Tables\Actions\Action::make('facturer')
                    ->label('Facturer')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->visible(fn(Order $r) => $r->isInstallee() && is_null($r->invoice_id))
                    ->requiresConfirmation()
                    ->modalHeading('Créer la facture pour cette commande ?')
                    ->modalDescription(fn(Order $r) => 'Une facture brouillon de '
                        . number_format((float)$r->total_ttc, 2, ',', ' ') . ' € TTC sera générée.')
                    ->action(function (Order $record) {
                        try {
                            // Charger les lignes avec leurs relations
                            $lines = $record->lines()->with(['magazine', 'plan'])->get();

                            // Calculer les totaux depuis les lignes réelles
                            $totalHt  = $lines->sum(fn($l) => (float) $l->total_ht);
                            $totalTtc = $lines->sum(fn($l) => (float) $l->total_ttc);
                            $tvaAmt   = round($totalHt * 2.10 / 100, 2);

                            // Créer la facture
                            $invoice = Invoice::create([
                                'client_id'       => $record->client_id,
                                'payer_client_id' => $record->client_id,
                                'invoice_date'    => now(),
                                'due_date'        => now()->addDays(30),
                                'subtotal'        => $totalHt,
                                'tax_rate'        => 2.10,
                                'tax_amount'      => $tvaAmt,
                                'total'           => $totalTtc,
                                'status'          => InvoiceStatus::Draft,
                                'notes'           => 'Commande ' . $record->number,
                            ]);

                            // Créer les lignes de facture
                            foreach ($lines as $line) {
                                $ht  = (float) $line->total_ht;
                                $ttc = (float) $line->total_ttc;

                                // Description enrichie façon facture de référence
                                $planName = $line->plan?->name ?? 'Abonnement';
                                $magName  = $line->magazine?->name ?? '';
                                $startDate = $line->start_date?->format('d/m/Y') ?? now()->format('d/m/Y');

                                $description = 'Abonnement à partir du ' . $startDate
                                    . ' pour l\'offre "' . $planName
                                    . ($magName ? ' (' . $magName . ')' : '') . '"';

                                \App\Models\InvoiceLine::create([
                                    'invoice_id'  => $invoice->id,
                                    'description' => $description,
                                    'quantity'    => 1,
                                    'unit_price'  => $ht,
                                    'total'       => $ht,
                                ]);
                            }

                            // Lier la commande à la facture
                            $record->update(['invoice_id' => $invoice->id]);

                            Notification::make()
                                ->title('Facture ' . $invoice->invoice_number . ' créée')
                                ->body(number_format($totalTtc, 2, ',', ' ') . ' € TTC — ' . $lines->count() . ' ligne(s)')
                                ->success()
                                ->send();

                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Erreur lors de la facturation')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('annuler')
                    ->label('Annuler')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn(Order $r) => $r->isDraft() || $r->isValidee())
                    ->requiresConfirmation()
                    ->action(function (Order $record) {
                        try {
                            app(OrderService::class)->cancel($record);
                            Notification::make()->title('Commande annulée')->warning()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit'   => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
