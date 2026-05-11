<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Enums\SubscriptionMode;
use App\Enums\SubscriptionStatus;
use App\Enums\SupportType;
use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Client;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Abonnements';
    protected static ?string $navigationLabel = 'Abonnements';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'active')->count() ?: null;
    }

    public static function form(Form $form): Form
    {
        $payerOptions = Client::where('is_payer', true)
            ->where('status', 'active')
            ->get()
            ->mapWithKeys(fn(Client $c) => [$c->id => "{$c->client_number} - {$c->full_name}"])
            ->toArray();

        $clientOptions = Client::where('status', 'active')
            ->get()
            ->mapWithKeys(fn(Client $c) => [$c->id => "{$c->client_number} - {$c->full_name}"])
            ->toArray();

        return $form->schema([
            Forms\Components\Section::make('Payeur et Beneficiaire')->schema([
                Forms\Components\Toggle::make('is_sponsored')
                    ->label('Paye par un tiers')
                    ->live()
                    ->dehydrated(false)
                    ->default(fn(?Subscription $r) => $r !== null && $r->payer_client_id !== null && $r->payer_client_id !== $r->client_id)
                    ->afterStateHydrated(fn($component, ?Subscription $record) => $component->state(
                        $record !== null && $record->payer_client_id !== null && $record->payer_client_id !== $record->client_id
                    ))
                    ->columnSpanFull(),
                Forms\Components\Select::make('payer_client_id')
                    ->label('Compte payeur')
                    ->options($payerOptions)
                    ->searchable()
                    ->visible(fn(Get $get) => $get('is_sponsored'))
                    ->required(fn(Get $get) => $get('is_sponsored')),
                Forms\Components\Select::make('client_id')
                    ->label(fn(Get $get) => $get('is_sponsored') ? 'Beneficiaire' : 'Client')
                    ->options($clientOptions)
                    ->searchable()
                    ->required(),
            ])->columns(2),

            Forms\Components\Section::make('Publication et Formule')->schema([
                Forms\Components\TextInput::make('subscription_number')
                    ->label('N')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),
                Forms\Components\Select::make('magazine_id')
                    ->label('Publication')
                    ->relationship('magazine', 'name')
                    ->required()
                    ->live()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('subscription_plan_id')
                    ->label('Formule')
                    ->relationship(
                        'subscriptionPlan',
                        'name',
                        fn($query, Get $get) => $query
                            ->when($get('magazine_id'), fn($q, $id) => $q->where('magazine_id', $id))
                            ->where('is_active', true)
                    )
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options(collect(SubscriptionStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                    ->default('pending')
                    ->required(),
                Forms\Components\Select::make('support_type')
                    ->label('Support')
                    ->options(collect(SupportType::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                    ->required(),
                Forms\Components\Select::make('mode')
                    ->label('Mode')
                    ->options(collect(SubscriptionMode::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                    ->required()
                    ->live(),
            ])->columns(2),

            Forms\Components\Section::make('Periode')->schema([
                Forms\Components\DatePicker::make('start_date')->label('Debut')->required(),
                Forms\Components\DatePicker::make('end_date')->label('Fin')->visible(fn(Get $get) => $get('mode') === 'duration'),
                Forms\Components\TextInput::make('issues_total')->label('Nb numeros')->numeric()->visible(fn(Get $get) => $get('mode') === 'issues'),
                Forms\Components\TextInput::make('issues_delivered')->label('Nb livres')->numeric()->default(0),
            ])->columns(2),

            Forms\Components\Section::make('Paiement')->schema([
                Forms\Components\TextInput::make('amount_paid')->label('Montant')->numeric()->prefix('EUR'),
                Forms\Components\Select::make('payment_method')
                    ->label('Mode')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()])),
                Forms\Components\TextInput::make('payment_reference')->label('Reference'),
                
            ])->columns(2),

            Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subscription_number')
                    ->label('N')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('client.last_name')
                    ->label('Beneficiaire')
                    ->formatStateUsing(fn($record) => $record->client?->full_name ?? '-')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('client', function (Builder $q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                              ->orWhere('company_name', 'like', "%{$search}%")
                              ->orWhere('client_number', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('payer.last_name')
                    ->label('Payeur')
                    ->formatStateUsing(fn($record) => $record->payer?->full_name ?? $record->payer?->company_name)
                    ->placeholder('= Benef.')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('magazine.name')
                    ->label('Publication')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('subscriptionPlan.name')
                    ->label('Formule')
                    ->sortable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('support_type')
                    ->label('Support')
                    ->formatStateUsing(fn(mixed $state) => (is_string($state) ? SupportType::from($state) : $state)->label())
                    ->badge()
                    ->color(fn(mixed $state) => match(is_string($state) ? $state : $state->value) {
                        'paper' => 'warning',
                        'digital' => 'info',
                        'combined' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn(mixed $state) => (is_string($state) ? SubscriptionStatus::from($state) : $state)->label())
                    ->badge()
                    ->color(fn(mixed $state) => (is_string($state) ? SubscriptionStatus::from($state) : $state)->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Debut')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn($record) => $record->end_date && $record->end_date->isPast() ? 'danger' : ($record->end_date && $record->end_date->isBefore(now()->addDays(30)) ? 'warning' : null)),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Montant')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // ── Statut ──
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(SubscriptionStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                    ->multiple()
                    ->preload(),

                // ── Publication ──
                Tables\Filters\SelectFilter::make('magazine_id')
                    ->label('Publication')
                    ->relationship('magazine', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                // ── Formule ──
                Tables\Filters\SelectFilter::make('subscription_plan_id')
                    ->label('Formule')
                    ->relationship('subscriptionPlan', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                // ── Support ──
                Tables\Filters\SelectFilter::make('support_type')
                    ->label('Support')
                    ->options(collect(SupportType::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                    ->multiple(),

                // ── Mode ──
                Tables\Filters\SelectFilter::make('mode')
                    ->label('Mode')
                    ->options(collect(SubscriptionMode::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()])),

                // ── Mode de paiement ──
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Mode de paiement')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                    ->multiple(),

                // ── Client / Beneficiaire (recherche autocomplete) ──
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client / Beneficiaire')
                    ->relationship('client', 'last_name')
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->client_number} - {$record->full_name}")
                    ->searchable()
                    ->preload(false),

                // ── Payeur (recherche autocomplete) ──
                Tables\Filters\SelectFilter::make('payer_client_id')
                    ->label('Payeur')
                    ->relationship('payer', 'last_name')
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->client_number} - {$record->full_name}")
                    ->searchable()
                    ->preload(false),

                // ── Dates ──
                Tables\Filters\Filter::make('date_range')
                    ->label('Periode de debut')
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('start_from')
                                ->label('Du'),
                            Forms\Components\DatePicker::make('start_until')
                                ->label('Au'),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['start_from'] ?? null, fn(Builder $query, $date) => $query->where('start_date', '>=', $date))
                            ->when($data['start_until'] ?? null, fn(Builder $query, $date) => $query->where('start_date', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['start_from'] ?? null) $indicators[] = 'Debut depuis: ' . \Carbon\Carbon::parse($data['start_from'])->format('d/m/Y');
                        if ($data['start_until'] ?? null) $indicators[] = 'Debut avant: ' . \Carbon\Carbon::parse($data['start_until'])->format('d/m/Y');
                        return $indicators;
                    }),

                // ── Expiration ──
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expire dans 30 jours')
                    ->toggle()
                    ->query(fn(Builder $query) => $query
                        ->where('status', 'active')
                        ->whereNotNull('end_date')
                        ->where('end_date', '<=', now()->addDays(30))
                        ->where('end_date', '>=', now())
                    ),

                Tables\Filters\Filter::make('expired')
                    ->label('Expires')
                    ->toggle()
                    ->query(fn(Builder $query) => $query
                        ->whereNotNull('end_date')
                        ->where('end_date', '<', now())
                    ),

                // ── Renouvellement auto ──
                Tables\Filters\TernaryFilter::make('auto_renew')
                    ->label('Renouvellement auto')
                    ->placeholder('Tous')
                    ->trueLabel('Avec renouvellement')
                    ->falseLabel('Sans renouvellement'),

                // ── Montant ──
                Tables\Filters\Filter::make('amount_range')
                    ->label('Fourchette de montant')
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('amount_min')
                                ->label('Min')
                                ->numeric()
                                ->prefix('EUR'),
                            Forms\Components\TextInput::make('amount_max')
                                ->label('Max')
                                ->numeric()
                                ->prefix('EUR'),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['amount_min'] ?? null, fn(Builder $query, $amount) => $query->where('amount_paid', '>=', $amount))
                            ->when($data['amount_max'] ?? null, fn(Builder $query, $amount) => $query->where('amount_paid', '<=', $amount));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['amount_min'] ?? null) $indicators[] = 'Montant min: ' . $data['amount_min'] . ' EUR';
                        if ($data['amount_max'] ?? null) $indicators[] = 'Montant max: ' . $data['amount_max'] . ' EUR';
                        return $indicators;
                    }),
            ])
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'edit'  => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
