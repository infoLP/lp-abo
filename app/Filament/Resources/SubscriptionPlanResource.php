<?php

namespace App\Filament\Resources;

use App\Enums\SubscriptionMode;
use App\Enums\SupportType;
use App\Filament\Resources\SubscriptionPlanResource\Pages;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Paramètres';
    protected static ?string $navigationLabel = 'Formules';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Select::make('magazine_id')
                    ->label('Publication')
                    ->relationship('magazine', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required(),
                Forms\Components\Hidden::make('slug'),
                Forms\Components\Select::make('support_type')
                    ->label('Support')
                    ->options(collect(SupportType::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                    ->required(),
                Forms\Components\Select::make('mode')
                    ->label('Mode')
                    ->options(collect(SubscriptionMode::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('duration_months')
                    ->label('Duree (mois)')
                    ->numeric()
                    ->visible(fn(callable $get) => $get('mode') === 'duration'),
                Forms\Components\TextInput::make('issues_count')
                    ->label('Nb numeros')
                    ->numeric()
                    ->visible(fn(callable $get) => $get('mode') === 'issues'),
                Forms\Components\TextInput::make('price')
                    ->label('Prix')
                    ->numeric()
                    ->prefix('EUR')
                    ->required(),
                Forms\Components\Toggle::make('is_free')
                    ->label('Gratuit'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Ordre')
                    ->numeric()
                    ->default(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('magazine.name')
                    ->label('Publication')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Formule')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
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
                Tables\Columns\TextColumn::make('mode')
                    ->label('Mode')
                    ->formatStateUsing(fn(mixed $state) => (is_string($state) ? SubscriptionMode::from($state) : $state)->label())
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_months')
                    ->label('Duree')
                    ->formatStateUsing(fn($state) => $state ? $state . ' mois' : '-')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('issues_count')
                    ->label('Numeros')
                    ->formatStateUsing(fn($state) => $state ? $state . ' num.' : '-')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('formatted_price')
                    ->label('Prix')
                    ->sortable(query: fn(Builder $query, string $direction) => $query->orderBy('price', $direction))
                    ->alignEnd(),
                Tables\Columns\IconColumn::make('is_free')
                    ->label('Gratuit')
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->label('Abonnements')
                    ->counts('subscriptions')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
            ])
            ->defaultSort('magazine.name')
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('magazine_id')
                    ->label('Publication')
                    ->relationship('magazine', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('support_type')
                    ->label('Support')
                    ->options(collect(SupportType::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                    ->multiple(),
                Tables\Filters\SelectFilter::make('mode')
                    ->label('Mode')
                    ->options(collect(SubscriptionMode::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()])),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Actives')
                    ->falseLabel('Inactives'),
                Tables\Filters\TernaryFilter::make('is_free')
                    ->label('Tarification')
                    ->placeholder('Tous')
                    ->trueLabel('Gratuites')
                    ->falseLabel('Payantes'),
                Tables\Filters\Filter::make('price_range')
                    ->label('Fourchette de prix')
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('price_min')
                                ->label('Prix min')
                                ->numeric()
                                ->prefix('EUR'),
                            Forms\Components\TextInput::make('price_max')
                                ->label('Prix max')
                                ->numeric()
                                ->prefix('EUR'),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['price_min'] ?? null, fn(Builder $query, $price) => $query->where('price', '>=', $price))
                            ->when($data['price_max'] ?? null, fn(Builder $query, $price) => $query->where('price', '<=', $price));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['price_min'] ?? null) $indicators[] = 'Prix min: ' . $data['price_min'] . ' EUR';
                        if ($data['price_max'] ?? null) $indicators[] = 'Prix max: ' . $data['price_max'] . ' EUR';
                        return $indicators;
                    }),
            ])
            ->filtersFormColumns(3)
            ->actions([
                    Tables\Actions\EditAction::make()
                        ->label('Modifier'),

                    Action::make('duplicate')
                        ->label('Dupliquer')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->modalHeading('Dupliquer la formule')
                        ->modalSubmitActionLabel('Dupliquer')
                        ->form([
                            Select::make('magazine_id')
                                ->label('Publication de destination')
                                ->options(fn () => \App\Models\Magazine::orderBy('name')->pluck('name', 'id'))
                                ->default(fn (\App\Models\SubscriptionPlan $record) => $record->magazine_id)
                                ->required()
                                ->searchable(),
                        ])
                        ->action(function (\App\Models\SubscriptionPlan $record, array $data) {
                            $copy = $record->replicate(['subscriptions_count']);
                            $copy->name = 'Copie de ' . $record->name;
                            $copy->magazine_id = $data['magazine_id'];
                            $copy->save();

                            Notification::make()
                                ->title('Formule dupliquée')
                                ->body('« ' . $copy->name . ' » a été créée.')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()
                        ->label('Supprimer')
                        ->modalHeading('Supprimer la formule')
                        ->modalDescription(fn (\App\Models\SubscriptionPlan $record) => 'Supprimer définitivement « ' . $record->name . ' » ?')
                        ->modalSubmitActionLabel('Supprimer')
                        ->before(function (\App\Models\SubscriptionPlan $record, DeleteAction $action) {
                            if ($record->subscriptions()->exists()) {
                                $count = $record->subscriptions()->count();
                                Notification::make()
                                    ->title('Suppression impossible')
                                    ->body("Cette formule est liée à {$count} abonnement(s) existant(s).")
                                    ->danger()
                                    ->send();
                                $action->cancel();
                            }
                        }),
                ])
                ->bulkActions([
                    BulkActionGroup::make([
                        BulkAction::make('duplicateSelected')
                            ->label('Dupliquer la sélection')
                            ->icon('heroicon-o-document-duplicate')
                            ->color('info')
                            ->modalHeading('Dupliquer les formules sélectionnées')
                            ->modalSubmitActionLabel('Dupliquer')
                            ->form([
                                Select::make('magazine_id')
                                    ->label('Publication de destination')
                                    ->options(fn () => \App\Models\Magazine::orderBy('name')->pluck('name', 'id'))
                                    ->required()
                                    ->searchable(),
                            ])
                            ->action(function (\Illuminate\Support\Collection $records, array $data) {
                                foreach ($records as $record) {
                                    $copy = $record->replicate(['subscriptions_count']);
                                    $copy->name = 'Copie de ' . $record->name;
                                    $copy->magazine_id = $data['magazine_id'];
                                    $copy->save();
                                }
                                Notification::make()
                                    ->title($records->count() . ' formule(s) dupliquée(s)')
                                    ->success()
                                    ->send();
                            })
                            ->deselectRecordsAfterCompletion(),

                        DeleteBulkAction::make()
                            ->label('Supprimer la sélection')
                            ->modalHeading('Supprimer les formules sélectionnées')
                            ->modalDescription('Seules les formules sans abonnement seront supprimées.')
                            ->modalSubmitActionLabel('Supprimer')
                            ->before(function (\Illuminate\Support\Collection $records, DeleteBulkAction $action) {
                                $blocked = $records->filter(fn ($r) => $r->subscriptions()->exists());
                                if ($blocked->isNotEmpty()) {
                                    $names = $blocked->pluck('name')->join(', ');
                                    Notification::make()
                                        ->title('Suppression partielle impossible')
                                        ->body("Ces formules ont des abonnements liés et ne seront pas supprimées : {$names}.")
                                        ->warning()
                                        ->send();
                                    // On retire les bloquées de la collection
                                    $records->each(function ($r) use ($blocked) {
                                        if ($blocked->contains('id', $r->id)) {
                                            $r->preventDeleting = true;
                                        }
                                    });
                                }
                            })
                            ->using(function (\Illuminate\Support\Collection $records) {
                                $deleted = 0;
                                foreach ($records as $record) {
                                    if (!$record->subscriptions()->exists()) {
                                        $record->delete();
                                        $deleted++;
                                    }
                                }
                                if ($deleted > 0) {
                                    Notification::make()
                                        ->title("{$deleted} formule(s) supprimée(s)")
                                        ->success()
                                        ->send();
                                }
                            })
                            ->deselectRecordsAfterCompletion(),
                    ]),
                ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
