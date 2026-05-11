<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\Action::make('nouvelle_commande')
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
                })];
    }

    protected function getTableQuery(): Builder
    {
        $query = Order::query();

        return match ($this->activeTab) {
            'brouillon'         => $query->where('status', 'brouillon'),
            'validee'           => $query->where('status', 'validee'),
            'validee_non_payee' => $query->where('status', 'validee')->whereNull('invoice_id'),
            'installee'         => $query->where('status', 'installee'),
            default             => $query,
        };
    }

    public function getTabs(): array
    {
        return [
            'all'               => Tab::make('Toutes')
                                    ->badge(Order::count()),
            'brouillon'         => Tab::make('Brouillons')
                                    ->badge(Order::where('status', 'brouillon')->count())
                                    ->badgeColor('gray'),
            'validee'           => Tab::make('Validées')
                                    ->badge(Order::where('status', 'validee')->count())
                                    ->badgeColor('warning'),
            'validee_non_payee' => Tab::make('⚠ Non payées')
                                    ->badge(Order::where('status', 'validee')->whereNull('invoice_id')->count())
                                    ->badgeColor('danger'),
            'installee'         => Tab::make('Installées')
                                    ->badge(Order::where('status', 'installee')->count())
                                    ->badgeColor('success'),
        ];
    }
}
