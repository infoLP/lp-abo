<?php
namespace App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Nouvelle facture')];
    }

    public function getTabs(): array
    {
        return [
            'toutes' => Tab::make('Toutes'),
            'brouillon' => Tab::make('Brouillons')
                ->modifyQueryUsing(fn(Builder $q) => $q->where('status', InvoiceStatus::Draft))
                ->badge(fn() => \App\Models\Invoice::where('status', InvoiceStatus::Draft)->count()),
            'envoyees' => Tab::make('Envoyées')
                ->modifyQueryUsing(fn(Builder $q) => $q->where('status', InvoiceStatus::Sent))
                ->badge(fn() => \App\Models\Invoice::where('status', InvoiceStatus::Sent)->count()),
            'retard' => Tab::make('En retard')
                ->modifyQueryUsing(fn(Builder $q) => $q->where('status', InvoiceStatus::Overdue))
                ->badge(fn() => \App\Models\Invoice::where('status', InvoiceStatus::Overdue)->count())
                ->badgeColor('danger'),
            'payees' => Tab::make('Payées')
                ->modifyQueryUsing(fn(Builder $q) => $q->where('status', InvoiceStatus::Paid)),
        ];
    }
}
