<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use App\Models\Subscription;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('nouvelle_commande')
                ->label('Nouvelle commande')
                ->color('primary')
                ->modalHeading('Sélectionner le client payeur')
                ->modalWidth('lg')
                ->form([
                    \Filament\Forms\Components\Select::make('payer_id')
                        ->label('Client payeur')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search) {
                            return \App\Models\Client::where('status', 'active')
                                ->where(function ($q) use ($search) {
                                    $q->where('last_name', 'like', "%{$search}%")
                                      ->orWhere('first_name', 'like', "%{$search}%")
                                      ->orWhere('company_name', 'like', "%{$search}%")
                                      ->orWhere('client_number', 'like', "%{$search}%")
                                      ->orWhere('email', 'like', "%{$search}%");
                                })
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn ($c) => [
                                    $c->id => $c->client_number . ' — ' . $c->full_name . ($c->company_name ? ' (' . $c->company_name . ')' : ''),
                                ]);
                        })
                        ->required()
                        ->helperText('Recherchez par nom, raison sociale, n° client ou email'),
                    \Filament\Forms\Components\Select::make('beneficiary_id')
                        ->label('Bénéficiaire (si différent)')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search) {
                            return \App\Models\Client::where('status', 'active')
                                ->where(function ($q) use ($search) {
                                    $q->where('last_name', 'like', "%{$search}%")
                                      ->orWhere('first_name', 'like', "%{$search}%")
                                      ->orWhere('company_name', 'like', "%{$search}%")
                                      ->orWhere('client_number', 'like', "%{$search}%");
                                })
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn ($c) => [
                                    $c->id => $c->client_number . ' — ' . $c->full_name,
                                ]);
                        })
                        ->helperText('Laisser vide si identique au payeur'),
                ])
                ->modalSubmitActionLabel('Créer la commande')
                ->action(function (array $data) {
                    $url = route('filament.admin.resources.orders.create', [
                        'payer_id'       => $data['payer_id'],
                        'beneficiary_id' => $data['beneficiary_id'] ?? null,
                    ]);
                    return redirect($url);
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = Subscription::query();

        return match ($this->activeTab) {
            'active'    => $query->where('status', 'active'),
            'expired'   => $query->where('status', 'expired'),
            'suspended' => $query->where('status', 'suspended'),
            'cancelled' => $query->where('status', 'cancelled'),
            'pending'   => $query->where('status', 'pending'),
            'trial'     => $query->where('status', 'trial'),
            default     => $query,
        };
    }

    public function getTabs(): array
    {
        return [
            'all'       => Tab::make('Tous')
                            ->badge(Subscription::count()),
            'active'    => Tab::make('Actifs')
                            ->badge(Subscription::where('status', 'active')->count())
                            ->badgeColor('success'),
            'pending'   => Tab::make('En attente')
                            ->badge(Subscription::where('status', 'pending')->count())
                            ->badgeColor('warning'),
            'expired'   => Tab::make('Expirés')
                            ->badge(Subscription::where('status', 'expired')->count())
                            ->badgeColor('danger'),
            'suspended' => Tab::make('Suspendus')
                            ->badge(Subscription::where('status', 'suspended')->count())
                            ->badgeColor('gray'),
            'cancelled' => Tab::make('Annulés')
                            ->badge(Subscription::where('status', 'cancelled')->count())
                            ->badgeColor('gray'),
        ];
    }
}
