<x-filament-panels::page>

    {{-- ══════════════════════════════════════════════════════════
         SECTION 1 : PURGE UNITAIRE
    ══════════════════════════════════════════════════════════ --}}
    <div class="rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 p-5 mb-6">
        <h2 class="font-semibold text-amber-800 dark:text-amber-300 mb-3 flex items-center gap-2">
            <x-filament::icon icon="heroicon-o-magnifying-glass" class="w-5 h-5" />
            Purge d'un client spécifique
        </h2>

        <div class="flex items-end gap-3 mb-4">
            <div class="flex-1 max-w-xs">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Code client (ex : CLI-000015)
                </label>
                <input type="text"
                    wire:model.defer="searchCode"
                    wire:keydown.enter="searchClient"
                    placeholder="CLI-000015 ou 15"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm py-2 px-3 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            </div>
            <x-filament::button wire:click="searchClient" color="warning" size="sm">
                Rechercher
            </x-filament::button>
        </div>

        @if($targetClient)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 mb-4">

                {{-- Info client --}}
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="font-semibold text-gray-800 dark:text-gray-200 text-sm">
                            {{ $targetClient['client_number'] }} — {{ $targetClient['name'] }}
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $targetClient['email'] }}
                            · Type : {{ $targetClient['type'] === 'company' ? 'Entreprise' : 'Particulier' }}
                            · Statut : {{ $targetClient['status'] }}
                        </p>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700">
                        Cible de la purge
                    </span>
                </div>

                {{-- Données liées --}}
                <div class="grid grid-cols-3 md:grid-cols-6 gap-2 text-center mb-4">
                    @foreach([
                        ['label' => 'Abonnements', 'count' => $targetStats['subscriptions'] ?? 0],
                        ['label' => 'Commandes',   'count' => $targetStats['orders'] ?? 0],
                        ['label' => 'Lignes cmd',  'count' => $targetStats['order_lines'] ?? 0],
                        ['label' => 'Factures',    'count' => $targetStats['invoices'] ?? 0],
                        ['label' => 'Règlements',  'count' => $targetStats['payments'] ?? 0],
                        ['label' => 'Adresses',    'count' => $targetStats['addresses'] ?? 0],
                    ] as $item)
                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-2">
                        <p class="text-lg font-bold {{ $item['count'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                            {{ $item['count'] }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $item['label'] }}</p>
                    </div>
                    @endforeach
                </div>

                {{-- Bouton purge --}}
                <div class="flex justify-end">
                    <x-filament::button
                        color="danger"
                        icon="heroicon-o-trash"
                        wire:click="purgeTargetClient"
                        wire:confirm="⚠️ IRRÉVERSIBLE — Supprimer définitivement le client {{ $targetClient['client_number'] }} ({{ $targetClient['name'] }}) et TOUTES ses données liées ?"
                    >
                        Purger {{ $targetClient['client_number'] }}
                    </x-filament::button>
                </div>
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════
         SECTION 2 : PURGE EN MASSE (CLI-000011+)
    ══════════════════════════════════════════════════════════ --}}
    <div class="rounded-xl border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-700 p-5 mb-6">
        <div class="flex items-start gap-3 mb-4">
            <x-filament::icon icon="heroicon-o-exclamation-triangle"
                class="w-6 h-6 text-red-600 dark:text-red-400 mt-0.5 shrink-0" />
            <div>
                <p class="font-semibold text-red-800 dark:text-red-300">
                    Purge en masse — Données de test uniquement
                </p>
                <p class="text-sm text-red-700 dark:text-red-400 mt-1">
                    Supprime <strong>définitivement</strong> tous les clients
                    <strong>CLI-000011 et au-delà</strong>, ainsi que toutes leurs données liées.
                    <br>Action <strong>irréversible</strong>.
                </p>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 text-center">
                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ $stats['total'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 mt-1">Total clients</p>
            </div>
            <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-900/20 p-4 text-center">
                <p class="text-3xl font-bold text-green-700 dark:text-green-400">{{ $stats['to_keep'] ?? 0 }}</p>
                <p class="text-sm text-green-600 mt-1">À conserver (≤ CLI-000010)</p>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-900/20 p-4 text-center">
                <p class="text-3xl font-bold text-red-700 dark:text-red-400">{{ $stats['to_delete'] ?? 0 }}</p>
                <p class="text-sm text-red-600 mt-1">À supprimer (≥ CLI-000011)</p>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 text-center">
                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                    {{ ($stats['subscriptions'] ?? 0) + ($stats['orders'] ?? 0) + ($stats['invoices'] ?? 0) + ($stats['addresses'] ?? 0) }}
                </p>
                <p class="text-sm text-gray-500 mt-1">Données liées</p>
            </div>
        </div>

        @if(($stats['to_delete'] ?? 0) > 0)
            {{-- Détail --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 mb-4">
                <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-3">
                    Données supprimées avec les {{ $stats['to_delete'] }} clients :
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4 text-gray-400" />
                        <span class="text-gray-600 dark:text-gray-400">{{ $stats['subscriptions'] ?? 0 }} abonnements</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-shopping-cart" class="w-4 h-4 text-gray-400" />
                        <span class="text-gray-600 dark:text-gray-400">{{ $stats['orders'] ?? 0 }} commandes</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-document-duplicate" class="w-4 h-4 text-gray-400" />
                        <span class="text-gray-600 dark:text-gray-400">{{ $stats['invoices'] ?? 0 }} factures</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-banknotes" class="w-4 h-4 text-gray-400" />
                        <span class="text-gray-600 dark:text-gray-400">{{ $stats['payments'] ?? 0 }} règlements</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-map-pin" class="w-4 h-4 text-gray-400" />
                        <span class="text-gray-600 dark:text-gray-400">{{ $stats['addresses'] ?? 0 }} adresses</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <x-filament::button
                    color="danger"
                    icon="heroicon-o-trash"
                    wire:click="runPurge"
                    wire:confirm="⚠️ IRRÉVERSIBLE — Confirmer la suppression définitive de {{ $stats['to_delete'] }} clients et toutes leurs données liées ?"
                >
                    Lancer la purge en masse
                </x-filament::button>
            </div>
        @else
            <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-900/20 p-6 text-center">
                <x-filament::icon icon="heroicon-o-check-circle"
                    class="w-10 h-10 text-green-500 mx-auto mb-2" />
                <p class="font-semibold text-green-700 dark:text-green-400">Base propre</p>
                <p class="text-sm text-green-600 mt-1">Aucun client au-delà de CLI-000010.</p>
            </div>
        @endif
    </div>

</x-filament-panels::page>
