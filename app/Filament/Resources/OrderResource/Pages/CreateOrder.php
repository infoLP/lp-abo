<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Client;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    public function mount(): void
    {
        parent::mount();

        $payerId        = request('payer_id');
        $beneficiaryId  = request('beneficiary_id');
        $beneficiaryIds = request('beneficiary_ids'); // "1,2,3"

        if (! $payerId) return;

        $payer = Client::find($payerId);
        if (! $payer) return;

        // ── Résolution du/des bénéficiaire(s) ────────────────────────────────
        $beneficiary    = null;
        $multiBenefIds  = null;

        if ($beneficiaryIds) {
            // Multi-bénéficiaires : on stocke le tableau d'IDs
            $ids = array_filter(array_map('intval', explode(',', $beneficiaryIds)));
            if (! empty($ids)) {
                $multiBenefIds = array_values($ids);
            }
        } elseif ($beneficiaryId && (int) $beneficiaryId !== (int) $payerId) {
            $beneficiary = Client::find($beneficiaryId);
        }

        // ── Adresse de facturation = PAYEUR ───────────────────────────────────
        $billing = [
            'billing_name'        => $payer->default_billing_address?->l1 ?? $payer->company_name ?? '',
            'billing_address1'    => $payer->default_billing_address?->l4 ?? '',
            'billing_address2'    => $payer->default_billing_address?->l5 ?? '',
            'billing_address3'    => '',
            'billing_postal_code' => $payer->default_billing_address?->l6_postal_code ?? '',
            'billing_city'        => $payer->default_billing_address?->l6_city ?? '',
            'billing_cedex'       => $payer->default_billing_address?->l6_cedex ?? '',
            'billing_country'     => $payer->default_billing_address?->l7_country ?? 'FR',
        ];

        // ── Adresse de livraison ──────────────────────────────────────────────
        // Multi-bénéficiaires : pas d'adresse unique → on laisse vide (à compléter)
        // Bénéficiaire unique : adresse du bénéficiaire
        // Sinon : adresse du payeur
        if ($multiBenefIds) {
            $delivery = [
                'delivery_company'     => '',
                'delivery_recipient'   => '',
                'delivery_address1'    => '',
                'delivery_address2'    => '',
                'delivery_address3'    => '',
                'delivery_postal_code' => '',
                'delivery_city'        => '',
                'delivery_cedex'       => '',
                'delivery_country'     => 'FR',
            ];
        } else {
            $ship      = $beneficiary ?? $payer;
            $delivAddr = $ship->default_delivery_address ?? $ship->default_billing_address;
            $delivery  = [
                'delivery_company'     => $delivAddr?->l1 ?? '',
                'delivery_recipient'   => $delivAddr?->l1 ?? '',
                'delivery_address1'    => $delivAddr?->l4 ?? '',
                'delivery_address2'    => $delivAddr?->l5 ?? '',
                'delivery_address3'    => '',
                'delivery_postal_code' => $delivAddr?->l6_postal_code ?? '',
                'delivery_city'        => $delivAddr?->l6_city ?? '',
                'delivery_cedex'       => $delivAddr?->l6_cedex ?? '',
                'delivery_country'     => $delivAddr?->l7_country ?? 'FR',
            ];
        }

        // ── Remplir le formulaire ─────────────────────────────────────────────
        $this->form->fill(array_merge(
            [
                'client_id'       => $payer->id,
                'beneficiary_id'  => $beneficiary?->id,
                'beneficiary_ids' => $multiBenefIds,
                'order_date'      => now()->format('Y-m-d'),
                'status'          => 'brouillon',
            ],
            $billing,
            $delivery
        ));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status']           = 'brouillon';
        $data['discount_percent'] = (float) ($data['discount_percent'] ?? 0);
        $data['discount_amount']  = (float) ($data['discount_amount']  ?? 0);
        $data['subtotal']         = 0;
        $data['total_ht']         = 0;
        $data['total_tva']        = 0;
        $data['total_ttc']        = 0;

        unset($data['save_delivery_to_client'], $data['use_different_delivery']);

        return $data;
    }
}
