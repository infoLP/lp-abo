<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Client;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Déclencher la validation si demandé via URL
        if (request('action') === 'valider' && $this->record->isDraft()) {
            try {
                app(\App\Services\OrderService::class)->validate($this->record);
                Notification::make()->title('Commande validée')->success()->send();
                $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
            } catch (\Throwable $e) {
                Notification::make()->title($e->getMessage())->danger()->send();
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // ── Retour fiche client ───────────────────────────────────────────
            Actions\Action::make('retour_client')
                ->label('Fiche client')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn() => \App\Filament\Resources\ClientResource::getUrl('edit', [
                    'record' => $this->record->client_id,
                ])),

            // ── Valider ───────────────────────────────────────────────────────
            Actions\Action::make('valider')
                ->label('Valider la commande')
                ->icon('heroicon-o-check-circle')
                ->color('warning')
                ->visible(fn() => $this->record->isDraft())
                ->requiresConfirmation()
                ->modalHeading('Valider la commande ?')
                ->modalDescription('La commande sera validée et prête pour la facturation. Cette action est irréversible.')
                ->modalSubmitActionLabel('Oui, valider')
                ->modalCancelActionLabel('Non, plus tard')
                ->action(function () {
                    try {
                        app(OrderService::class)->validate($this->record);
                        Notification::make()->title('Commande validée')->success()->send();
                        $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            // ── Installer ─────────────────────────────────────────────────────
            Actions\Action::make('installer')
                ->label('Installer')
                ->icon('heroicon-o-star')
                ->color('success')
                ->visible(fn() => $this->record->isValidee())
                ->requiresConfirmation()
                ->modalHeading('Installer la commande ?')
                ->modalDescription('Les abonnements seront créés et le client aura accès aux numéros.')
                ->modalSubmitActionLabel('Oui, installer')
                ->action(function () {
                    try {
                        app(OrderService::class)->install($this->record);
                        Notification::make()->title('Commande installée')->success()->send();
                        $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            // ── Annuler ───────────────────────────────────────────────────────
            Actions\Action::make('annuler')
                ->label('Annuler la commande')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn() => $this->record->isDraft() || $this->record->isValidee())
                ->requiresConfirmation()
                ->modalHeading('Annuler la commande ?')
                ->modalDescription('Cette commande sera annulée définitivement.')
                ->action(function () {
                    try {
                        app(OrderService::class)->cancel($this->record);
                        Notification::make()->title('Commande annulée')->warning()->send();
                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            Actions\DeleteAction::make()
                ->visible(fn() => $this->record->isDraft()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    /**
     * Après sauvegarde : recalculer les totaux
     * + proposer validation si brouillon
     */
    protected function afterSave(): void
    {
        $this->recalculateTotals();

        // Proposer validation si brouillon via notification avec action
        if ($this->record->isDraft()) {
            Notification::make()
                ->title('Commande sauvegardée')
                ->body('Voulez-vous valider la commande ' . $this->record->number . ' maintenant ?')
                ->warning()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('valider')
                        ->label('Valider maintenant')
                        ->button()
                        ->color('warning')
                        ->url($this->getResource()::getUrl('edit', ['record' => $this->record]) . '?action=valider'),
                    \Filament\Notifications\Actions\Action::make('plus_tard')
                        ->label('Plus tard')
                        ->close(),
                ])
                ->send();
        } else {
            Notification::make()
                ->title('Commande sauvegardée')
                ->success()
                ->send();
        }
    }

    private function recalculateTotals(): void
    {
        $order = $this->record->fresh(['lines']);

        $subtotal = $order->lines->sum('total_ttc');
        $tva      = $order->lines->sum('total_tva');
        $discount = $order->discount_percent > 0
            ? round($subtotal * $order->discount_percent / 100, 2)
            : (float) $order->discount_amount;

        $totalTtc = max(0, $subtotal - $discount);
        $totalTva = $subtotal > 0 ? round($tva * (1 - $discount / $subtotal), 2) : 0;
        $totalHt  = $totalTtc - $totalTva;

        $order->updateQuietly([
            'subtotal'        => $subtotal,
            'discount_amount' => $discount,
            'total_ttc'       => $totalTtc,
            'total_tva'       => $totalTva,
            'total_ht'        => $totalHt,
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['lines'])) {
            foreach ($data['lines'] as &$line) {
                $price         = (float) ($line['unit_price'] ?? 0);
                $rate          = 2.10;
                $tva           = round($price * $rate / (100 + $rate), 2);
                $line['total_ttc'] = $price;
                $line['total_tva'] = $tva;
                $line['total_ht']  = round($price - $tva, 2);
                $line['tva_rate']  = $rate;
            }
        }

        // Sauvegarder adresse livraison si demandé
        if (! empty($data['save_delivery_to_client'])) {
            $targetId = $this->record->beneficiary_id ?? $this->record->client_id;
            $target   = \App\Models\Client::find($targetId);
            if ($target) {
                $target->update([
                    // Les adresses sont gérées dans la table addresses
                ]);
            }
        }

        unset($data['save_delivery_to_client'], $data['use_different_delivery']);

        return $data;
    }

    /**
     * Action appelée depuis le modal de confirmation
     */
    public function validateOrder(): void
    {
        try {
            app(OrderService::class)->validate($this->record);
            Notification::make()->title('Commande validée')->success()->send();
            $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
        } catch (\Throwable $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}
// Ce bloc est à fusionner dans la classe — voir ci-dessous
