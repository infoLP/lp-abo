<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MagazineResource\Pages;
use App\Models\AccountingAssignment;
use App\Models\Magazine;
use App\Models\VatRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MagazineResource extends Resource
{
    protected static ?string $model          = Magazine::class;
    protected static ?string $navigationIcon  = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Contenu';
    protected static ?string $navigationLabel = 'Publications';
    protected static ?string $modelLabel      = 'Publication';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Onglets')->tabs([

                // ── Onglet 1 : Informations générales ────────────────────────
                Forms\Components\Tabs\Tab::make('Informations')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom complet')->required()->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, callable $set) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('short_name')
                            ->label('Nom court (abréviation)')->maxLength(50),
                        Forms\Components\TextInput::make('slug')
                            ->required()->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('issn')
                            ->label('ISSN'),
                        Forms\Components\Select::make('frequency')
                            ->label('Fréquence')
                            ->options([
                                'weekly'    => 'Hebdomadaire',
                                'monthly'   => 'Mensuel',
                                'bimonthly' => 'Bimestriel',
                                'quarterly' => 'Trimestriel',
                            ])
                            ->default('weekly'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Ordre')->numeric()->default(0),
                    ])->columns(2),

                // ── Onglet 2 : Comptabilité ───────────────────────────────────
                Forms\Components\Tabs\Tab::make('Comptabilité')
                    ->icon('heroicon-o-calculator')
                    ->schema([

                        Forms\Components\Placeholder::make('info_compta')
                            ->label('')
                            ->content('Définissez les comptes de vente applicables à cette publication selon le type de vente et la zone géographique du client.')
                            ->columnSpanFull(),

                        // Répéteur : une ligne par type (abonnement / revue / livraison)
                        Forms\Components\Repeater::make('accountingAssignments')
                            ->label('Affectations comptables')
                            ->relationship('accountingAssignments')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Type de vente')
                                    ->options(AccountingAssignment::types())
                                    ->required(),

                                Forms\Components\Select::make('vat_rate_id')
                                    ->label('Taux TVA')
                                    ->options(VatRate::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->nullable(),

                                Forms\Components\Section::make('Codes par zone géographique')
                                    ->columns(3)
                                    ->compact()
                                    ->schema([
                                        Forms\Components\TextInput::make('metropole_accounting_code')
                                            ->label('Métropole')->maxLength(20)->placeholder('707001'),
                                        Forms\Components\TextInput::make('corse_accounting_code')
                                            ->label('Corse')->maxLength(20)->placeholder('707001'),
                                        Forms\Components\TextInput::make('dom_accounting_code')
                                            ->label('DOM')->maxLength(20)->placeholder('707001'),
                                        Forms\Components\TextInput::make('ue_sans_intracom_accounting_code')
                                            ->label('UE sans intracom')->maxLength(20)->placeholder('707001'),
                                        Forms\Components\TextInput::make('ue_avec_intracom_accounting_code')
                                            ->label('UE avec intracom')->maxLength(20)->placeholder('707001'),
                                        Forms\Components\TextInput::make('international_accounting_code')
                                            ->label('International')->maxLength(20)->placeholder('707001'),
                                    ]),

                                Forms\Components\TextInput::make('label')
                                    ->label('Libellé personnalisé (optionnel)')
                                    ->placeholder('Ex : Vente Abonnement EJG')
                                    ->maxLength(255),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')->default(true)
                                    ->inline(false),
                            ])
                            ->columns(2)
                            ->addActionLabel('Ajouter une affectation')
                            ->orderColumn('sort_order')
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string =>
                                AccountingAssignment::types()[$state['type'] ?? ''] ?? 'Nouvelle affectation'
                            )
                            ->columnSpanFull(),
                    ]),

            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Publication')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('short_name')
                    ->label('Abr.')->badge(),
                Tables\Columns\TextColumn::make('frequency')
                    ->label('Fréquence')->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'weekly'    => 'Hebdomadaire',
                        'monthly'   => 'Mensuel',
                        'bimonthly' => 'Bimestriel',
                        'quarterly' => 'Trimestriel',
                        default     => $state,
                    }),
                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->label('Abon.')->counts('subscriptions'),
                Tables\Columns\TextColumn::make('accountingAssignments_count')
                    ->label('Comptes')
                    ->counts('accountingAssignments')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'warning'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMagazines::route('/'),
            'create' => Pages\CreateMagazine::route('/create'),
            'edit'   => Pages\EditMagazine::route('/{record}/edit'),
        ];
    }
}
