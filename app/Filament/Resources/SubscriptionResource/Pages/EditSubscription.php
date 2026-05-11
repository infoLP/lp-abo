<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── Retour fiche client ───────────────────────────────────────────
            Actions\Action::make('retour_client')
                ->label('Fiche client')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn() => ClientResource::getUrl('edit', [
                    'record' => $this->record->client_id,
                ])),

            Actions\DeleteAction::make(),
        ];
    }
}
