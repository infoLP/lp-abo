<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private ClientAccountService $accountService
    ) {}

    /**
     * Valide une commande brouillon → validée
     */
    public function validate(Order $order): Order
    {
        if (! $order->isDraft()) {
            throw new \RuntimeException('Seule une commande brouillon peut être validée.');
        }

        DB::transaction(function () use ($order) {
            $order->recalculateTotals();
            $order->update([
                'status'       => OrderStatus::Validee,
                'validated_at' => now(),
            ]);
        });

        return $order->fresh();
    }

    /**
     * Installe une commande validée → installée
     * Crée les abonnements pour chaque bénéficiaire × chaque ligne
     */
    public function install(Order $order): Order
    {
        if (! $order->isValidee()) {
            throw new \RuntimeException('Seule une commande validée peut être installée.');
        }

        // Clients ayant reçu un abonnement numérique/combiné → pour envoi email après transaction
        $digitalBeneficiaryIds = [];

        DB::transaction(function () use ($order, &$digitalBeneficiaryIds) {
            $order->load('lines');

            // ── Résolution des bénéficiaires ──────────────────────────────────
            $beneficiaryIds = [];

            if (! empty($order->beneficiary_ids)) {
                // Sécurité : vérifier que chaque bénéficiaire est bien rattaché au payeur
                $rawIds = $order->beneficiary_ids;
                $beneficiaryIds = \App\Models\Client::whereIn('id', $rawIds)
                    ->where(function ($q) use ($order) {
                        $q->where('payer_client_id', $order->client_id)
                          ->orWhere('id', $order->client_id);
                    })
                    ->pluck('id')
                    ->toArray();

                if (empty($beneficiaryIds)) {
                    throw new \RuntimeException(
                        'Aucun bénéficiaire valide : ils doivent être rattachés au client payeur.'
                    );
                }

                if (count($beneficiaryIds) !== count($rawIds)) {
                    \Illuminate\Support\Facades\Log::warning(
                        "OrderService: beneficiary_ids filtrés pour commande #{$order->id}",
                        ['demandés' => $rawIds, 'autorisés' => $beneficiaryIds]
                    );
                }
            } elseif ($order->beneficiary_id) {
                // Vérifier le bénéficiaire unique
                $ben = \App\Models\Client::where('id', $order->beneficiary_id)
                    ->where(function ($q) use ($order) {
                        $q->where('payer_client_id', $order->client_id)
                          ->orWhere('id', $order->client_id);
                    })
                    ->first();

                if (!$ben) {
                    throw new \RuntimeException(
                        'Bénéficiaire non autorisé : il doit être rattaché au client payeur.'
                    );
                }
                $beneficiaryIds = [$order->beneficiary_id];
            } else {
                $beneficiaryIds = [$order->client_id];
            }

            // ── Création des abonnements : 1 par bénéficiaire × par ligne ─────
            foreach ($beneficiaryIds as $beneficiaryId) {
                foreach ($order->lines as $line) {
                    $subscription = $this->createSubscription($order, $line, (int) $beneficiaryId);

                    if (count($beneficiaryIds) === 1) {
                        $line->update(['subscription_id' => $subscription->id]);
                    }

                    // Repérer les bénéficiaires avec abonnement numérique/combiné
                    $supportVal = is_string($subscription->support_type)
                        ? $subscription->support_type
                        : $subscription->support_type->value;

                    if (in_array($supportVal, ['digital', 'combined'])) {
                        $digitalBeneficiaryIds[] = (int) $beneficiaryId;
                    }
                }
            }

            $order->update([
                'status'       => OrderStatus::Installee,
                'installed_at' => now(),
            ]);
        });

        // ── Envoi emails hors transaction (évite rollback si email échoue) ───
        $uniqueIds = array_unique($digitalBeneficiaryIds);
        foreach ($uniqueIds as $clientId) {
            $client = Client::find($clientId);
            if ($client) {
                try {
                    $this->accountService->handleAccountForClient($client);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error(
                        "ClientAccountService error for client #{$clientId}: " . $e->getMessage()
                    );
                }
            }
        }

        return $order->fresh();
    }

    /**
     * Crée un abonnement depuis une ligne de commande pour un bénéficiaire donné
     */
    private function createSubscription(Order $order, OrderLine $line, int $beneficiaryId): Subscription
    {
        $plan    = $line->plan;
        $endDate = null;

        $modeVal = is_string($plan->mode) ? $plan->mode : $plan->mode->value;

        if ($modeVal === 'duration' && $plan->duration_months) {
            $endDate = Carbon::parse($line->start_date)
                             ->addMonths($plan->duration_months)
                             ->subDay();
        }

        return Subscription::create([
            'client_id'            => $beneficiaryId,
            'payer_client_id'      => $order->client_id,
            'magazine_id'          => $line->magazine_id,
            'subscription_plan_id' => $line->subscription_plan_id,
            'support_type'         => $line->support ?? $plan->support_type,
            'mode'                 => $plan->mode,
            'start_date'           => $line->start_date,
            'end_date'             => $endDate,
            'issues_total'         => $line->issues_count ?? $plan->issues_count,
            'issues_remaining'     => $line->issues_count ?? $plan->issues_count,
            'amount_paid'          => $line->total_ttc,
            'status'               => SubscriptionStatus::ACTIVE,
            'order_id'             => $order->id,
        ]);
    }

    /**
     * Annule une commande brouillon ou validée
     */
    public function cancel(Order $order): Order
    {
        if ($order->isInstallee()) {
            throw new \RuntimeException('Une commande installée ne peut pas être annulée.');
        }

        $order->update(['status' => OrderStatus::Annulee]);

        return $order->fresh();
    }
}
