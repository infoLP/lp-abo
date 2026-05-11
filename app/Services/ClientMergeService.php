<?php

namespace App\Services;

use App\Models\Client;
use App\Models\DuplicateGroup;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ClientMergeService
{
    protected array $transferLog = [];

    public function merge(DuplicateGroup $group, int $masterClientId, array $fieldOverrides = []): array
    {
        $this->transferLog = [];
        $group->load('items.client');

        $masterItem = $group->items->firstWhere('client_id', $masterClientId);
        if (!$masterItem) {
            throw new \InvalidArgumentException("Le client #{$masterClientId} ne fait pas partie de ce groupe.");
        }

        $master = Client::findOrFail($masterClientId);
        $secondaryClients = $group->items
            ->where('client_id', '!=', $masterClientId)
            ->pluck('client')
            ->filter();

        DB::beginTransaction();
        try {
            $this->applyFieldOverrides($master, $secondaryClients, $fieldOverrides);
            $this->enrichMaster($master, $secondaryClients);
            $this->transferSubscriptions($master, $secondaryClients);
            $this->transferInvoices($master, $secondaryClients);
            $this->transferPayments($master, $secondaryClients);
            $this->transferPayerRelations($master, $secondaryClients);
            $this->mergeNotes($master, $secondaryClients);
            $this->mergeCustomFields($master, $secondaryClients);
            $this->archiveSecondaries($master, $secondaryClients);
            $master->save();

            $group->setMaster($masterClientId);
            $group->markMerged(
                auth()->id(),
                "Fusion vers #{$master->client_number}. " . count($this->transferLog) . " operations."
            );

            DB::commit();
            Log::info("ClientMerge: Groupe #{$group->id} fusionne vers client #{$masterClientId}", [
                'operations' => count($this->transferLog),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("ClientMerge: Erreur fusion groupe #{$group->id} - " . $e->getMessage());
            throw $e;
        }

        return $this->transferLog;
    }

    protected function applyFieldOverrides(Client $master, $secondaryClients, array $fieldOverrides): void
    {
        foreach ($fieldOverrides as $field => $sourceClientId) {
            if ((int) $sourceClientId === $master->id) continue;
            $sourceClient = $secondaryClients->firstWhere('id', $sourceClientId);
            if ($sourceClient && $sourceClient->{$field}) {
                $oldValue = $master->{$field};
                $master->{$field} = $sourceClient->{$field};
                $this->transferLog[] = "Champ '{$field}' : pris du client #{$sourceClientId} (remplace '{$oldValue}')";
            }
        }
    }

    protected function enrichMaster(Client $master, $secondaryClients): void
    {
        $fields = [
            'email', 'company_email', 'phone', 'mobile',
            'siret', 'vat_number', 'company_name',
            'address_line1', 'address_line2', 'address_line3',
            'postal_code', 'city', 'cedex',
            'delivery_address_line1', 'delivery_address_line2', 'delivery_address_line3',
            'delivery_postal_code', 'delivery_city', 'delivery_cedex',
            'external_code',
        ];

        foreach ($secondaryClients as $secondary) {
            foreach ($fields as $field) {
                if (empty($master->{$field}) && !empty($secondary->{$field})) {
                    $master->{$field} = $secondary->{$field};
                    $this->transferLog[] = "Enrichissement '{$field}' depuis client #{$secondary->id}";
                }
            }
        }
    }

    protected function transferSubscriptions(Client $master, $secondaryClients): void
    {
        foreach ($secondaryClients as $secondary) {
            $subs = Subscription::where('client_id', $secondary->id)->get();
            foreach ($subs as $sub) {
                $sub->update(['client_id' => $master->id]);
                $this->transferLog[] = "Abonnement #{$sub->id} transfere de client #{$secondary->id}";
            }

            if (Schema::hasColumn('subscriptions', 'payer_client_id')) {
                $payerSubs = Subscription::where('payer_client_id', $secondary->id)->get();
                foreach ($payerSubs as $sub) {
                    $sub->update(['payer_client_id' => $master->id]);
                    $this->transferLog[] = "Abonnement #{$sub->id} : payeur transfere de #{$secondary->id}";
                }
            }
        }
    }

    protected function transferInvoices(Client $master, $secondaryClients): void
    {
        foreach ($secondaryClients as $secondary) {
            $invoices = Invoice::where('client_id', $secondary->id)->get();
            foreach ($invoices as $invoice) {
                $invoice->update(['client_id' => $master->id]);
                $this->transferLog[] = "Facture {$invoice->invoice_number} transferee de client #{$secondary->id}";
            }
        }
    }

    protected function transferPayments(Client $master, $secondaryClients): void
    {
        foreach ($secondaryClients as $secondary) {
            $payments = Payment::where('client_id', $secondary->id)->get();
            foreach ($payments as $payment) {
                $payment->update(['client_id' => $master->id]);
                $this->transferLog[] = "Paiement {$payment->payment_number} transfere de client #{$secondary->id}";
            }
        }
    }

    protected function transferPayerRelations(Client $master, $secondaryClients): void
    {
        // Table payers : colonnes = id, client_id (beneficiaire), payer_client_id (payeur)
        foreach ($secondaryClients as $secondary) {
            // 1. Le secondaire est PAYEUR de certains clients
            $asPayer = DB::table('payers')
                ->where('payer_client_id', $secondary->id)->get();

            foreach ($asPayer as $rel) {
                $exists = DB::table('payers')
                    ->where('payer_client_id', $master->id)
                    ->where('client_id', $rel->client_id)
                    ->exists();

                if (!$exists && (int) $rel->client_id !== $master->id) {
                    DB::table('payers')->where('id', $rel->id)
                        ->update(['payer_client_id' => $master->id]);
                    $this->transferLog[] = "Relation payeur pour beneficiaire #{$rel->client_id} transferee";
                } else {
                    DB::table('payers')->where('id', $rel->id)->delete();
                    $this->transferLog[] = "Relation payeur doublon supprimee";
                }
            }

            // 2. Le secondaire est BENEFICIAIRE
            $asBenef = DB::table('payers')
                ->where('client_id', $secondary->id)->get();

            foreach ($asBenef as $rel) {
                $exists = DB::table('payers')
                    ->where('payer_client_id', $rel->payer_client_id)
                    ->where('client_id', $master->id)
                    ->exists();

                if (!$exists && (int) $rel->payer_client_id !== $master->id) {
                    DB::table('payers')->where('id', $rel->id)
                        ->update(['client_id' => $master->id]);
                    $this->transferLog[] = "Relation beneficiaire #{$secondary->id} transferee vers #{$master->id}";
                } else {
                    DB::table('payers')->where('id', $rel->id)->delete();
                    $this->transferLog[] = "Relation beneficiaire doublon supprimee";
                }
            }

            // 3. Sur la table clients directement (champ payer_client_id)
            Client::where('payer_client_id', $secondary->id)
                ->update(['payer_client_id' => $master->id]);
        }
    }

    protected function mergeNotes(Client $master, $secondaryClients): void
    {
        $allNotes = [];
        if (!empty($master->notes)) {
            $allNotes[] = $master->notes;
        }
        foreach ($secondaryClients as $secondary) {
            if (!empty($secondary->notes)) {
                $allNotes[] = "--- Notes du compte #{$secondary->client_number} (fusionne le " . now()->format('d/m/Y') . ") ---\n" . $secondary->notes;
            }
        }
        if (count($allNotes) > 1) {
            $master->notes = implode("\n\n", $allNotes);
            $this->transferLog[] = "Notes fusionnees depuis " . count($allNotes) . " comptes";
        }
    }

    protected function mergeCustomFields(Client $master, $secondaryClients): void
    {
        $masterFields = $master->custom_fields ?? [];
        foreach ($secondaryClients as $secondary) {
            $secondaryFields = $secondary->custom_fields ?? [];
            foreach ($secondaryFields as $key => $value) {
                if (!empty($value) && empty($masterFields[$key] ?? null)) {
                    $masterFields[$key] = $value;
                    $this->transferLog[] = "Champ perso '{$key}' enrichi depuis client #{$secondary->id}";
                }
            }
        }
        $master->custom_fields = $masterFields;
    }

    protected function archiveSecondaries(Client $master, $secondaryClients): void
    {
        foreach ($secondaryClients as $secondary) {
            $secondary->update([
                'status' => 'archived',
                'archived_at' => now(),
                'archived_reason' => "Fusionne vers #{$master->client_number}",
                'archived_by' => auth()->id(),
                'notes' => ($secondary->notes ? $secondary->notes . "\n\n" : '')
                    . "== COMPTE FUSIONNE ==\n"
                    . "Fusionne vers le compte #{$master->client_number} le " . now()->format('d/m/Y H:i') . "\n"
                    . "Par : " . (auth()->user()?->name ?? 'Systeme'),
            ]);
            $this->transferLog[] = "Client #{$secondary->client_number} archive (fusionne vers #{$master->client_number})";
        }
    }

    public static function preview(DuplicateGroup $group): array
    {
        $group->load('items.client');

        return $group->items->map(function ($item) {
            $client = $item->client;
            if (!$client) return null;
            return [
                'id' => $client->id,
                'client_number' => $client->client_number,
                'display_name' => $client->full_name ?: $client->company_name,
                'type' => $client->type,
                'email' => $client->email,
                'company_email' => $client->company_email,
                'phone' => $client->phone,
                'mobile' => $client->mobile,
                'company_name' => $client->company_name,
                'siret' => $client->siret,
                'city' => $client->city,
                'postal_code' => $client->postal_code,
                'status' => $client->status,
                'created_at' => $client->created_at?->format('d/m/Y'),
                'subscriptions_count' => $client->subscriptions()->count(),
                'active_subscriptions_count' => $client->subscriptions()->where('status', 'active')->count(),
                'invoices_count' => $client->invoices()->count(),
                'payments_count' => $client->payments()->count(),
                'has_notes' => !empty($client->notes),
                'has_custom_fields' => !empty($client->custom_fields),
            ];
        })->filter()->values()->toArray();
    }
}
