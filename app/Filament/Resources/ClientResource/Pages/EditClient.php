<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\OrderResource;
use App\Models\Client;
use App\Models\Order;
use App\Enums\OrderStatus;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [

            // ── Nouvelle commande ─────────────────────────────────────────────
            Actions\Action::make('new_order')
                ->label('Nouvelle commande')
                ->color('primary')
                ->visible(fn() => $this->record->isActive())

                ->form(function (): array {
                    $client        = $this->record;
                    $beneficiaries = $client->directBeneficiaries()
                        ->where('status', 'active')
                        ->orderBy('last_name')
                        ->orderBy('company_name')
                        ->get();

                    // Option de base : pour ce client
                    $options = [
                        'self' => '👤 Ce client — '
                            . $client->full_name
                            . ($client->client_number ? ' [' . $client->client_number . ']' : ''),
                    ];

                    if ($beneficiaries->isNotEmpty()) {
                        $options['beneficiaries'] = '👥 Un ou plusieurs bénéficiaires ('
                            . $beneficiaries->count() . ')';
                    }

                    $fields = [
                        Forms\Components\Radio::make('target')
                            ->label('Créer la commande pour')
                            ->options($options)
                            ->default('self')
                            ->live()
                            ->required()
                            ->columnSpanFull(),
                    ];

                    if ($beneficiaries->isNotEmpty()) {
                        $beneficiaryOptions = $beneficiaries
                            ->mapWithKeys(fn(Client $b) => [
                                $b->id => trim(
                                    ($b->client_number ? '[' . $b->client_number . '] ' : '')
                                    . $b->full_name
                                    . ($b->company_name ? ' — ' . $b->company_name : '')
                                ),
                            ])
                            ->toArray();

                        $fields[] = Forms\Components\CheckboxList::make('beneficiary_ids')
                            ->label('Sélectionner les bénéficiaires')
                            ->options($beneficiaryOptions)
                            ->bulkToggleable()
                            ->columns(1)
                            ->gridDirection('row')
                            ->required(fn(Get $get) => $get('target') === 'beneficiaries')
                            ->visible(fn(Get $get)  => $get('target') === 'beneficiaries')
                            ->helperText('Les abonnements seront créés sur chaque bénéficiaire à l\'installation.')
                            ->columnSpanFull();
                    }

                    return $fields;
                })

                ->modalHeading('Nouvelle commande')
                ->modalSubmitActionLabel('Créer')
                ->modalWidth('lg')

                ->action(function (array $data): void {
                    $client = $this->record;
                    $target = $data['target'] ?? 'self';

                    // ── Pour ce client ────────────────────────────────────────
                    if ($target === 'self') {
                        if ($client->payer_client_id) {
                            // Ce client est bénéficiaire d'un autre payeur
                            $this->redirect(OrderResource::getUrl('create', [
                                'payer_id'       => $client->payer_client_id,
                                'beneficiary_id' => $client->id,
                            ]));
                            return;
                        }
                        // Commande en son propre nom
                        $this->redirect(OrderResource::getUrl('create', [
                            'payer_id' => $client->id,
                        ]));
                        return;
                    }

                    // ── Pour des bénéficiaires ────────────────────────────────
                    $beneficiaryIds = array_values($data['beneficiary_ids'] ?? []);

                    if (empty($beneficiaryIds)) {
                        Notification::make()
                            ->title('Aucun bénéficiaire sélectionné')
                            ->warning()
                            ->send();
                        return;
                    }

                    // 1 bénéficiaire → redirect avec beneficiary_id simple
                    if (count($beneficiaryIds) === 1) {
                        $this->redirect(OrderResource::getUrl('create', [
                            'payer_id'       => $client->id,
                            'beneficiary_id' => $beneficiaryIds[0],
                        ]));
                        return;
                    }

                    // N bénéficiaires → redirect avec liste IDs séparés par virgule
                    $this->redirect(OrderResource::getUrl('create', [
                        'payer_id'        => $client->id,
                        'beneficiary_ids' => implode(',', $beneficiaryIds),
                    ]));
                }),

            // ── Activer ───────────────────────────────────────────────────────
            Actions\Action::make('activate')
                ->label('Activer')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn() => ! $this->record->isActive())
                ->action(function () {
                    $this->record->activate();
                    Notification::make()->title('Activé')->success()->send();
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),

            // ── Désactiver ────────────────────────────────────────────────────
            Actions\Action::make('deactivate')
                ->label('Désactiver')
                ->color('warning')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\TextInput::make('reason')->label('Raison'),
                ])
                ->visible(fn() => $this->record->isActive())
                ->action(function (array $data) {
                    $this->record->deactivate($data['reason'] ?? '');
                    Notification::make()->title('Désactivé')->success()->send();
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),

            // ── Archiver ──────────────────────────────────────────────────────
            Actions\Action::make('archive')
                ->label('Archiver')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\TextInput::make('reason')->label('Raison')->required(),
                ])
                ->visible(fn() => ! $this->record->isArchived())
                ->action(function (array $data) {
                    $this->record->archive($data['reason'] ?? '', auth()->id());
                    Notification::make()->title('Archivé')->success()->send();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            // ── Restaurer ─────────────────────────────────────────────────────
            Actions\Action::make('unarchive')
                ->label('Restaurer')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn() => $this->record->isArchived())
                ->action(function () {
                    $this->record->restoreFromArchive();
                    Notification::make()->title('Restauré')->success()->send();
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),
        ];
    }

    // ── Hooks EditRecord ──────────────────────────────────────────────────────

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    public function saveAddress(array $data): void
    {
        $client = $this->record;

        if (!empty($data['is_default'])) {
            $client->addresses()
                ->where('id', '!=', $data['id'] ?? 0)
                ->update(['is_default' => false]);
        }

        if (!empty($data['id'])) {
            $client->addresses()->where('id', $data['id'])->update(\Illuminate\Support\Arr::except($data, ['id']));
        } else {
            $client->addresses()->create($data);
        }

        $this->record->refresh();

        \Filament\Notifications\Notification::make()
            ->title('Adresse enregistrée')
            ->success()
            ->send();
    }

    public function deleteAddress(int $id): void
    {
        $this->record->addresses()->where('id', $id)->delete();
        $this->record->refresh();

        \Filament\Notifications\Notification::make()
            ->title('Adresse supprimée')
            ->success()
            ->send();
    }

    public function checkRnvp(int $id): void
    {
        $address = $this->record->addresses()->find($id);
        if (!$address) return;

        $lines = array_filter([
            $address->l1, $address->l2, $address->l3, $address->l4, $address->l5,
            trim($address->l6_postal_code . ' ' . $address->l6_city),
        ]);

        $errors = [];
        foreach ($lines as $i => $line) {
            if (strlen($line) > 38) $errors[] = 'L' . ($i + 1) . ' dépasse 38 caractères';
        }
        if (empty($address->l1)) $errors[] = 'L1 (destinataire) obligatoire';
        if (empty($address->l4)) $errors[] = 'L4 (voie) obligatoire';
        if (empty($address->l6_postal_code)) $errors[] = 'Code postal obligatoire';
        if (empty($address->l6_city)) $errors[] = 'Ville obligatoire';

        $valid = empty($errors);
        $address->update([
            'rnvp_valid'      => $valid,
            'rnvp_checked_at' => now(),
            'rnvp_status'     => $valid ? 'OK' : implode(' | ', $errors),
        ]);
        $this->record->refresh();

        \Filament\Notifications\Notification::make()
            ->title($valid ? 'Adresse RNVP conforme' : 'Adresse non conforme')
            ->body($valid ? null : implode("\n", $errors))
            ->color($valid ? 'success' : 'danger')
            ->send();
    }

    public function changeOrderStatus(int $orderId, string $status): void
    {
        $order = Order::find($orderId);
        if (! $order) return;

        $newStatus = OrderStatus::from($status);
        $updates   = ['status' => $newStatus];

        if ($newStatus === OrderStatus::Validee)   $updates['validated_at'] = now();
        if ($newStatus === OrderStatus::Installee) $updates['installed_at'] = now();

        $order->update($updates);

        if ($newStatus === OrderStatus::Installee) {
            app(\App\Services\OrderService::class)->install($order->fresh());
        }

        $this->record->refresh();

        $labels = [
            'brouillon' => 'remise en brouillon',
            'validee'   => 'validée',
            'installee' => 'installée',
            'annulee'   => 'annulée',
        ];

        \Filament\Notifications\Notification::make()
            ->title('Commande ' . ($labels[$status] ?? $status))
            ->success()
            ->send();
    }
}
