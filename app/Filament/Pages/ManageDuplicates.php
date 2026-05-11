<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\DuplicateGroup;
use App\Services\ClientMergeService;
use App\Services\DuplicateDetectionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Database\Eloquent\Builder;

class ManageDuplicates extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-document-duplicate';
    protected static ?string $navigationLabel = 'Doublons';
    protected static ?string $title           = 'Detection et fusion de doublons';
    protected static ?string $navigationGroup = 'Paramètres';
    protected static ?int    $navigationSort  = 20;
    protected static string  $view            = 'filament.pages.manage-duplicates';

    public bool  $showMergeModal   = false;
    public ?int  $mergeGroupId     = null;
    public ?int  $selectedMasterId = null;
    public array $mergePreview     = [];
    public array $mergeLog         = [];
    public bool  $mergeComplete    = false;

    /**
     * Seuls admin et director peuvent gérer les fusions (opération irréversible)
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['admin', 'director']) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = DuplicateGroup::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('analyze')
                ->label('Lancer l\'analyse')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Lancer la detection de doublons')
                ->modalDescription('L\'analyse va scanner toute la base clients. Les resultats precedents en attente seront supprimes.')
                ->modalSubmitActionLabel('Lancer')
                ->action(function () {
                    abort_unless(auth()->user()?->hasRole(['admin', 'director']), 403);
                    try {
                        $service = new DuplicateDetectionService();
                        $stats   = $service->analyze();
                        $total   = array_sum($stats);

                        if ($total === 0) {
                            Notification::make()
                                ->title('Aucun doublon detecte')
                                ->body('La base clients ne contient pas de doublons identifiables.')
                                ->success()->send();
                        } else {
                            $details = collect($stats)
                                ->filter(fn($v) => $v > 0)
                                ->map(fn($v, $k) => match($k) {
                                    'email'        => "{$v} par email",
                                    'siret'        => "{$v} par SIRET",
                                    'name_postal'  => "{$v} par nom+CP",
                                    'phone'        => "{$v} par telephone",
                                    'company_city' => "{$v} par societe+ville",
                                    default        => "{$v} {$k}",
                                })->implode(', ');

                            Notification::make()
                                ->title("{$total} groupe(s) de doublons detecte(s)")
                                ->body($details)
                                ->warning()->duration(8000)->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Erreur lors de l\'analyse')
                            ->body($e->getMessage())
                            ->danger()->send();
                    }
                }),

            Action::make('clearDismissed')
                ->label('Vider les ignores')
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Supprimer les doublons ignores')
                ->modalDescription('Les groupes ignores seront supprimes et pourront etre re-detectes.')
                ->visible(fn() => DuplicateGroup::where('status', 'dismissed')->exists())
                ->action(function () {
                    abort_unless(auth()->user()?->hasRole(['admin', 'director']), 403);
                    $count = DuplicateGroup::where('status', 'dismissed')->delete();
                    Notification::make()
                        ->title("{$count} groupe(s) ignore(s) supprime(s)")
                        ->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DuplicateGroup::query()->with('items.client'))
            ->defaultSort('confidence_score', 'desc')
            ->columns([
                TextColumn::make('confidence_score')
                    ->label('Score')->suffix('%')->sortable()->badge()
                    ->color(fn(int $state): string => match(true) {
                        $state >= 90 => 'danger',
                        $state >= 70 => 'warning',
                        default      => 'gray',
                    })->alignCenter()->width('80px'),

                TextColumn::make('match_type')
                    ->label('Type')->sortable()
                    ->formatStateUsing(fn(string $state, $record): string => $record->match_type_label)
                    ->icon(fn(string $state, $record): string => $record->match_type_icon)
                    ->color(fn(string $state, $record): string => $record->match_type_color)
                    ->width('200px'),

                TextColumn::make('match_value')
                    ->label('Valeur commune')->searchable()->limit(50)
                    ->tooltip(fn($record) => $record->match_value),

                TextColumn::make('clients_count')
                    ->label('Clients')->sortable()->alignCenter()->badge()->color('info')->width('80px'),

                TextColumn::make('clients_list')
                    ->label('Comptes concernes')
                    ->state(function (DuplicateGroup $record): string {
                        return $record->items->map(function ($item) {
                            $c = $item->client;
                            if (!$c) return '(supprime)';
                            $name = $c->company_name ?: $c->full_name;
                            return "#{$c->client_number} {$name}";
                        })->implode(' | ');
                    })->wrap()->size(TextColumn\TextColumnSize::ExtraSmall),

                TextColumn::make('status')
                    ->label('Statut')->sortable()->badge()
                    ->formatStateUsing(fn(string $state, $record): string => $record->status_label)
                    ->color(fn(string $state, $record): string => $record->status_color)
                    ->width('100px'),

                TextColumn::make('detected_at')
                    ->label('Detecte le')->dateTime('d/m/Y H:i')->sortable()
                    ->size(TextColumn\TextColumnSize::ExtraSmall)->width('130px'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')
                    ->options(['pending' => 'En attente', 'merged' => 'Fusionne', 'dismissed' => 'Ignore'])
                    ->default('pending'),
                SelectFilter::make('match_type')->label('Type de doublon')
                    ->options(['email' => 'Email identique', 'siret' => 'SIRET identique', 'name_postal' => 'Nom + Prenom + CP', 'phone' => 'Telephone identique', 'company_city' => 'Societe + Ville']),
            ])
            ->actions([
                TableAction::make('merge')
                    ->label('Fusionner')->icon('heroicon-o-arrows-pointing-in')->color('primary')
                    ->visible(fn(DuplicateGroup $record): bool => $record->status === 'pending')
                    ->action(function (DuplicateGroup $record) {
                        abort_unless(auth()->user()?->hasRole(['admin', 'director']), 403);
                        $this->openMergeModal($record->id);
                    }),

                TableAction::make('dismiss')
                    ->label('Ignorer')->icon('heroicon-o-x-mark')->color('gray')
                    ->visible(fn(DuplicateGroup $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Ignorer ce groupe de doublons')
                    ->modalDescription('Ce groupe ne sera plus propose lors des prochaines analyses.')
                    ->action(function (DuplicateGroup $record) {
                        abort_unless(auth()->user()?->hasRole(['admin', 'director']), 403);
                        $record->dismiss(auth()->id(), 'Ignore manuellement');
                        Notification::make()->title('Groupe ignore')->success()->send();
                    }),

                TableAction::make('viewClients')
                    ->label('Voir les fiches')->icon('heroicon-o-eye')->color('gray')
                    ->url(function (DuplicateGroup $record): string {
                        $ids = $record->items->pluck('client_id')->toArray();
                        return \App\Filament\Resources\ClientResource::getUrl('edit', ['record' => $ids[0] ?? 0]);
                    })->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Aucun doublon detecte')
            ->emptyStateDescription('Cliquez sur "Lancer l\'analyse" pour scanner la base clients.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->poll(null)->striped();
    }

    public function openMergeModal(int $groupId): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'director']), 403);

        $group = DuplicateGroup::with('items.client')->findOrFail($groupId);

        $this->mergeGroupId     = $groupId;
        $this->mergePreview     = ClientMergeService::preview($group);
        $this->selectedMasterId = null;
        $this->mergeLog         = [];
        $this->mergeComplete    = false;
        $this->showMergeModal   = true;

        if (!empty($this->mergePreview)) {
            $best = collect($this->mergePreview)->sortByDesc(function ($c) {
                return ($c['subscriptions_count'] ?? 0) * 10
                    + ($c['invoices_count'] ?? 0) * 5
                    + ($c['payments_count'] ?? 0) * 3
                    + (!empty($c['email']) ? 2 : 0)
                    + (!empty($c['siret']) ? 2 : 0)
                    + (!empty($c['phone']) ? 1 : 0);
            })->first();
            $this->selectedMasterId = $best['id'] ?? null;
        }
    }

    public function selectMaster(int $clientId): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'director']), 403);
        $this->selectedMasterId = $clientId;
    }

    public function executeMerge(): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'director']), 403);

        if (!$this->mergeGroupId || !$this->selectedMasterId) {
            Notification::make()->title('Veuillez selectionner le compte a conserver')->danger()->send();
            return;
        }

        try {
            $group          = DuplicateGroup::findOrFail($this->mergeGroupId);
            $service        = new ClientMergeService();
            $this->mergeLog = $service->merge($group, $this->selectedMasterId);
            $this->mergeComplete = true;

            $master = Client::find($this->selectedMasterId);

            Notification::make()
                ->title('Fusion reussie !')
                ->body("Comptes fusionnes vers #{$master->client_number}. " . count($this->mergeLog) . " operation(s).")
                ->success()->duration(8000)->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erreur lors de la fusion')
                ->body($e->getMessage())
                ->danger()->duration(10000)->send();
        }
    }

    public function closeMergeModal(): void
    {
        $this->showMergeModal   = false;
        $this->mergeGroupId     = null;
        $this->mergePreview     = [];
        $this->selectedMasterId = null;
        $this->mergeLog         = [];
        $this->mergeComplete    = false;
    }

    public function getStats(): array
    {
        return DuplicateDetectionService::getStats();
    }
}
