<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VatRateResource\Pages;
use App\Models\VatRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VatRateResource extends Resource
{
    protected static ?string $model            = VatRate::class;
    protected static ?string $navigationIcon   = 'heroicon-o-percent-badge';
    protected static ?string $navigationGroup  = 'Paramètres';
    protected static ?string $navigationLabel  = 'Taux de TVA';
    protected static ?string $modelLabel       = 'Taux de TVA';
    protected static ?string $pluralModelLabel = 'Taux de TVA';
    protected static ?int    $navigationSort   = 5;

    // ── Formulaire ────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        $zoneSchema = fn(string $zone, string $label) => [
            Forms\Components\TextInput::make("{$zone}_rate")
                ->label('Taux (%)')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%')
                ->placeholder('0.00'),
            Forms\Components\TextInput::make("{$zone}_accounting_code")
                ->label('Code comptable')
                ->maxLength(20)
                ->placeholder('ex: 445712'),
        ];

        return $form->schema([

            // ── Identification ────────────────────────────────────────────────
            Forms\Components\Section::make('Identification')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(100)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Forms\Set $set, $record) {
                            // Slug auto uniquement à la création
                            if (! $record) {
                                $set('slug', \Illuminate\Support\Str::slug($state, '_'));
                            }
                        }),
                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true)
                        ->helperText('Identifiant technique, généré automatiquement'),
                    Forms\Components\TextInput::make('usage')
                        ->label('Usage / description')
                        ->maxLength(255)
                        ->placeholder('ex: Presse papier, livres…')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('sort_order')
                        ->label('Ordre d\'affichage')
                        ->numeric()
                        ->default(0),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Actif')
                        ->default(true),
                ])
                ->columns(2),

            // ── Taux par zone géographique ────────────────────────────────────
            Forms\Components\Section::make('Taux et codes comptables par zone')
                ->description('Renseignez le taux et le code comptable collecte pour chaque zone applicable.')
                ->schema([

                    Forms\Components\Fieldset::make('Métropole')
                        ->schema($zoneSchema('metropole', 'Métropole'))
                        ->columns(2),

                    Forms\Components\Fieldset::make('Corse')
                        ->schema($zoneSchema('corse', 'Corse'))
                        ->columns(2),

                    Forms\Components\Fieldset::make('DOM')
                        ->schema($zoneSchema('dom', 'DOM'))
                        ->columns(2),

                    Forms\Components\Fieldset::make('UE sans numéro intracom')
                        ->schema($zoneSchema('ue_sans_intracom', 'UE sans intracom'))
                        ->columns(2),

                    Forms\Components\Fieldset::make('UE avec numéro intracom')
                        ->schema($zoneSchema('ue_avec_intracom', 'UE avec intracom'))
                        ->columns(2),

                    Forms\Components\Fieldset::make('International (hors UE)')
                        ->schema($zoneSchema('international', 'International'))
                        ->columns(2),

                ])
                ->columns(3),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        $rateColumn = fn(string $zone, string $label) => Tables\Columns\TextColumn::make("{$zone}_rate")
            ->label($label)
            ->formatStateUsing(fn($state) => $state !== null && $state !== ''
                ? number_format((float) $state, 2, ',', ' ') . ' %'
                : '—')
            ->alignCenter();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->alignCenter()
                    ->width('50px'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('usage')
                    ->label('Usage')
                    ->searchable()
                    ->placeholder('—')
                    ->limit(50),
                $rateColumn('metropole',    'Métropole'),
                $rateColumn('corse',        'Corse'),
                $rateColumn('dom',          'DOM'),
                $rateColumn('ue_sans_intracom', 'UE (sans)'),
                $rateColumn('ue_avec_intracom', 'UE (avec)'),
                $rateColumn('international', 'Intl'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Modifier'),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn(VatRate $r) => $r->is_active ? 'Désactiver' : 'Activer')
                    ->icon(fn(VatRate $r) => $r->is_active
                        ? 'heroicon-o-eye-slash'
                        : 'heroicon-o-eye')
                    ->color(fn(VatRate $r) => $r->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (VatRate $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()
                            ->title($record->is_active ? 'Taux activé' : 'Taux désactivé')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer')
                    ->before(function (VatRate $record, Tables\Actions\DeleteAction $action) {
                        if ($record->assignments()->exists()) {
                            Notification::make()
                                ->title('Suppression impossible')
                                ->body('Ce taux est utilisé dans des affectations comptables.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([])
            ->striped();
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVatRates::route('/'),
            'create' => Pages\CreateVatRate::route('/create'),
            'edit'   => Pages\EditVatRate::route('/{record}/edit'),
        ];
    }
}
