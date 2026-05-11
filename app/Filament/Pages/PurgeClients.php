<?php

namespace App\Filament\Pages;

use App\Models\Client;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PurgeClients extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-trash';
    protected static ?string $navigationLabel = 'Purge clients';
    protected static ?string $navigationGroup = 'Paramètres';
    protected static ?string $slug            = 'purge-clients';
    protected static ?string $title           = 'Purge des clients';
    protected static ?int    $navigationSort  = 99;
    protected static string  $view            = 'filament.pages.purge-clients';

    public array  $stats        = [];
    public string $searchCode   = '';
    public ?array $targetClient = null;
    public array  $targetStats  = [];

    /**
     * Seuls admin et director peuvent accéder à cette page destructive
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['admin', 'director']) ?? false;
    }

    public function mount(): void
    {
        // Double vérification au montage
        abort_unless(auth()->user()?->hasRole(['admin', 'director']), 403);
        $this->loadStats();
    }

    // ══════════════════════════════════════════════════════════
    // PURGE EN MASSE (CLI-000011+)
    // ══════════════════════════════════════════════════════════

    private function getIdsToDelete(): array
    {
        return DB::table('clients')
            ->whereRaw("CAST(SUBSTRING(client_number, 5) AS UNSIGNED) > 10")
            ->pluck('id')
            ->toArray();
    }

    private function loadStats(): void
    {
        $ids = $this->getIdsToDelete();

        $this->stats = [
            'total'         => DB::table('clients')->count(),
            'to_keep'       => DB::table('clients')->whereNotIn('id', $ids ?: [0])->count(),
            'to_delete'     => count($ids),
            'subscriptions' => $ids ? DB::table('subscriptions')->whereIn('client_id', $ids)->count() : 0,
            'orders'        => $ids ? DB::table('orders')->whereIn('client_id', $ids)->count() : 0,
            'invoices'      => $ids ? DB::table('invoices')->whereIn('client_id', $ids)->count() : 0,
            'addresses'     => $ids ? DB::table('addresses')->whereIn('client_id', $ids)->count() : 0,
            'payments'      => $ids ? DB::table('payments')
                ->whereIn('invoice_id',
                    DB::table('invoices')->whereIn('client_id', $ids)->pluck('id')
                )->count() : 0,
        ];
    }

    public function runPurge(): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'director']), 403);

        $ids = $this->getIdsToDelete();
        if (empty($ids)) {
            Notification::make()->title('Rien à purger')->info()->send();
            return;
        }
        $counts = $this->deleteClientIds($ids);
        Notification::make()
            ->title('Purge en masse effectuée')
            ->body($this->formatCounts($counts))
            ->success()
            ->send();
        $this->loadStats();
    }

    // ══════════════════════════════════════════════════════════
    // PURGE UNITAIRE (par code client)
    // ══════════════════════════════════════════════════════════

    public function searchClient(): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'director']), 403);

        $code = trim($this->searchCode);
        $this->targetClient = null;
        $this->targetStats  = [];

        // Longueur minimum pour éviter les recherches trop larges
        if (strlen($code) < 3) {
            Notification::make()->title('Saisissez au moins 3 caractères')->warning()->send();
            return;
        }

        // Échapper les caractères spéciaux LIKE
        $safeLike = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $code);

        $client = DB::table('clients')
            ->where('client_number', $code)
            ->orWhere('client_number', 'like', "%{$safeLike}%")
            ->first();

        if (! $client) {
            Notification::make()->title("Aucun client trouvé pour \"{$code}\"")->warning()->send();
            return;
        }

        $id = $client->id;
        $this->targetClient = [
            'id'            => $id,
            'client_number' => $client->client_number,
            'name'          => $client->company_name ?: trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')),
            'email'         => $client->email ?? '',
            'type'          => $client->type ?? '',
            'status'        => $client->status ?? '',
        ];

        $invoiceIds = DB::table('invoices')->where('client_id', $id)->pluck('id')->toArray();
        $orderIds   = DB::table('orders')
            ->where(fn($q) => $q->where('client_id', $id)->orWhere('beneficiary_id', $id))
            ->pluck('id')->toArray();

        $this->targetStats = [
            'subscriptions' => DB::table('subscriptions')
                ->where(fn($q) => $q->where('client_id', $id)->orWhere('payer_client_id', $id))
                ->count(),
            'orders'        => count($orderIds),
            'order_lines'   => $orderIds ? DB::table('order_lines')->whereIn('order_id', $orderIds)->count() : 0,
            'invoices'      => count($invoiceIds),
            'payments'      => $invoiceIds ? DB::table('payments')->whereIn('invoice_id', $invoiceIds)->count() : 0,
            'addresses'     => DB::table('addresses')->where('client_id', $id)->count(),
        ];
    }

    public function purgeTargetClient(): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'director']), 403);

        if (! $this->targetClient) return;

        $id     = $this->targetClient['id'];
        $code   = $this->targetClient['client_number'];
        $counts = $this->deleteClientIds([$id]);

        Notification::make()
            ->title("Client {$code} purgé")
            ->body($this->formatCounts($counts))
            ->success()
            ->send();

        $this->targetClient = null;
        $this->targetStats  = [];
        $this->searchCode   = '';
        $this->loadStats();
    }

    // ══════════════════════════════════════════════════════════
    // SUPPRESSION COMMUNE
    // ══════════════════════════════════════════════════════════

    private function deleteClientIds(array $ids): array
    {
        $counts = [];

        DB::transaction(function () use ($ids, &$counts) {

            $orderIds = DB::table('orders')
                ->where(fn($q) => $q->whereIn('client_id', $ids)->orWhereIn('beneficiary_id', $ids))
                ->pluck('id')->toArray();

            if ($orderIds) {
                $counts['order_lines'] = DB::table('order_lines')->whereIn('order_id', $orderIds)->delete();
                $counts['orders']      = DB::table('orders')->whereIn('id', $orderIds)->delete();
            }

            $invoiceIds = DB::table('invoices')->whereIn('client_id', $ids)->pluck('id')->toArray();
            if ($invoiceIds) {
                DB::table('invoice_lines')->whereIn('invoice_id', $invoiceIds)->delete();
                $counts['payments'] = DB::table('payments')->whereIn('invoice_id', $invoiceIds)->delete();
                $counts['invoices'] = DB::table('invoices')->whereIn('id', $invoiceIds)->delete();
            }

            $counts['subscriptions'] = DB::table('subscriptions')
                ->where(fn($q) => $q->whereIn('client_id', $ids)->orWhereIn('payer_client_id', $ids))
                ->delete();

            $counts['addresses'] = DB::table('addresses')->whereIn('client_id', $ids)->delete();

            if (DB::getSchemaBuilder()->hasTable('payers')) {
                DB::table('payers')
                    ->where(fn($q) => $q->whereIn('client_id', $ids)->orWhereIn('payer_client_id', $ids))
                    ->delete();
            }

            DB::table('clients')
                ->whereIn('payer_client_id', $ids)
                ->whereNotIn('id', $ids)
                ->update(['payer_client_id' => null]);

            $counts['clients'] = DB::table('clients')->whereIn('id', $ids)->delete();
        });

        return $counts;
    }

    private function formatCounts(array $counts): string
    {
        $parts = [];
        if ($counts['clients'] ?? 0)      $parts[] = ($counts['clients'])       . ' client(s)';
        if ($counts['subscriptions'] ?? 0) $parts[] = ($counts['subscriptions']) . ' abonnement(s)';
        if ($counts['orders'] ?? 0)        $parts[] = ($counts['orders'])        . ' commande(s)';
        if ($counts['invoices'] ?? 0)      $parts[] = ($counts['invoices'])      . ' facture(s)';
        if ($counts['payments'] ?? 0)      $parts[] = ($counts['payments'])      . ' règlement(s)';
        if ($counts['addresses'] ?? 0)     $parts[] = ($counts['addresses'])     . ' adresse(s)';
        return implode(' · ', $parts) ?: 'Aucune donnée supprimée';
    }
}
