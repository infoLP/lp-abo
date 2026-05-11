<?php

namespace App\Filament\Resources\SubscriptionPlanResource\Pages;

use App\Filament\Resources\SubscriptionPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionPlan extends EditRecord
{
    protected static string $resource = SubscriptionPlanResource::class;

    // Bouton retour + suppression dans l'en-tête
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Retour à la liste')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url($this->getRedirectUrl()),

            Actions\DeleteAction::make(),
        ];
    }

    // Après sauvegarde → retour à la liste (les filtres Filament sont
    // persistés en session automatiquement, ils seront restaurés)
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}