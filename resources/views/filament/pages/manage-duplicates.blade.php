<x-filament-panels::page>

    {{-- STATS CARDS --}}
    @php $stats = $this->getStats(); @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-warning-50 dark:bg-warning-500/10">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-warning-500" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">En attente</p>
                    <p class="text-2xl font-bold text-warning-600 dark:text-warning-400">{{ $stats['pending'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-primary-50 dark:bg-primary-500/10">
                    <x-heroicon-o-users class="w-6 h-6 text-primary-500" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Clients concernes</p>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $stats['clients_concerned'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-success-50 dark:bg-success-500/10">
                    <x-heroicon-o-check-circle class="w-6 h-6 text-success-500" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Fusionnes</p>
                    <p class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $stats['merged'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-gray-50 dark:bg-gray-500/10">
                    <x-heroicon-o-x-circle class="w-6 h-6 text-gray-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ignores</p>
                    <p class="text-2xl font-bold text-gray-500">{{ $stats['dismissed'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    {{ $this->table }}

    {{-- MODAL DE FUSION --}}
    @if($showMergeModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         x-data x-on:keydown.escape.window="$wire.closeMergeModal()">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-6xl max-h-[90vh] overflow-y-auto mx-4">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-900 z-10">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-arrows-pointing-in class="w-6 h-6 text-primary-500" />
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        @if($mergeComplete) Fusion terminee @else Fusionner les comptes @endif
                    </h2>
                </div>
                <button wire:click="closeMergeModal" class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    <x-heroicon-o-x-mark class="w-5 h-5 text-gray-400" />
                </button>
            </div>

            @if(!$mergeComplete)
                {{-- Instruction --}}
                <div class="px-6 py-3 bg-primary-50 dark:bg-primary-500/10 border-b border-primary-100 dark:border-primary-500/20">
                    <p class="text-sm text-primary-700 dark:text-primary-300">
                        <x-heroicon-s-information-circle class="w-4 h-4 inline -mt-0.5" />
                        Selectionnez le <strong>compte a conserver</strong> (compte maitre). Les donnees des autres comptes seront transferees, puis les comptes secondaires seront archives.
                    </p>
                </div>

                {{-- Cartes comparatives --}}
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-{{ min(count($mergePreview), 3) }} gap-4">
                        @foreach($mergePreview as $client)
                        <div wire:click="selectMaster({{ $client['id'] }})"
                             class="relative rounded-xl border-2 p-4 cursor-pointer transition-all
                                {{ $selectedMasterId === $client['id']
                                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-500/10 ring-2 ring-primary-500/30'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">

                            @if($selectedMasterId === $client['id'])
                            <div class="absolute -top-3 left-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-500 text-white">
                                    <x-heroicon-s-star class="w-3 h-3" /> COMPTE MAITRE
                                </span>
                            </div>
                            @else
                            <div class="absolute -top-3 left-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                    Sera archive
                                </span>
                            </div>
                            @endif

                            <div class="mt-2 mb-3">
                                <p class="font-bold text-gray-900 dark:text-white text-base">{{ $client['display_name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    #{{ $client['client_number'] }} - {{ $client['type'] === 'company' ? 'Entreprise' : 'Particulier' }} - Cree le {{ $client['created_at'] }}
                                </p>
                            </div>

                            <div class="space-y-1.5 text-sm">
                                @if($client['email'])
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-envelope class="w-4 h-4 text-gray-400 shrink-0" />
                                    <span class="text-gray-700 dark:text-gray-300 truncate">{{ $client['email'] }}</span>
                                </div>
                                @endif
                                @if($client['phone'])
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-400 shrink-0" />
                                    <span class="text-gray-700 dark:text-gray-300">{{ $client['phone'] }}</span>
                                </div>
                                @endif
                                @if($client['company_name'])
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-building-office class="w-4 h-4 text-gray-400 shrink-0" />
                                    <span class="text-gray-700 dark:text-gray-300 truncate">{{ $client['company_name'] }}</span>
                                </div>
                                @endif
                                @if($client['siret'])
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-identification class="w-4 h-4 text-gray-400 shrink-0" />
                                    <span class="text-gray-700 dark:text-gray-300 font-mono text-xs">{{ $client['siret'] }}</span>
                                </div>
                                @endif
                                @if($client['city'])
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-map-pin class="w-4 h-4 text-gray-400 shrink-0" />
                                    <span class="text-gray-700 dark:text-gray-300">{{ $client['postal_code'] }} {{ $client['city'] }}</span>
                                </div>
                                @endif
                            </div>

                            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <p class="text-lg font-bold {{ $client['active_subscriptions_count'] > 0 ? 'text-success-600' : 'text-gray-400' }}">{{ $client['subscriptions_count'] }}</p>
                                    <p class="text-xs text-gray-500">Abonn.</p>
                                    @if($client['active_subscriptions_count'] > 0)
                                    <p class="text-xs text-success-500">{{ $client['active_subscriptions_count'] }} actif(s)</p>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-lg font-bold {{ $client['invoices_count'] > 0 ? 'text-primary-600' : 'text-gray-400' }}">{{ $client['invoices_count'] }}</p>
                                    <p class="text-xs text-gray-500">Factures</p>
                                </div>
                                <div>
                                    <p class="text-lg font-bold {{ $client['payments_count'] > 0 ? 'text-warning-600' : 'text-gray-400' }}">{{ $client['payments_count'] }}</p>
                                    <p class="text-xs text-gray-500">Reglem.</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    @if($selectedMasterId)
                    <div class="mt-6 p-4 rounded-lg bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-500/20">
                        <div class="flex gap-3">
                            <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-warning-500 shrink-0 mt-0.5" />
                            <div class="text-sm text-warning-700 dark:text-warning-300">
                                <p class="font-medium">Resume de la fusion :</p>
                                <ul class="mt-1 list-disc list-inside space-y-0.5">
                                    @php
                                        $master = collect($mergePreview)->firstWhere('id', $selectedMasterId);
                                        $secondaries = collect($mergePreview)->where('id', '!=', $selectedMasterId);
                                    @endphp
                                    <li>Compte conserve : <strong>#{{ $master['client_number'] }} {{ $master['display_name'] }}</strong></li>
                                    @foreach($secondaries as $sec)
                                    <li>Compte archive : #{{ $sec['client_number'] }} {{ $sec['display_name'] }}
                                        @if($sec['subscriptions_count'] > 0) - {{ $sec['subscriptions_count'] }} abonnement(s) transfere(s) @endif
                                        @if($sec['invoices_count'] > 0) - {{ $sec['invoices_count'] }} facture(s) transferee(s) @endif
                                    </li>
                                    @endforeach
                                    <li>Les donnees manquantes du maitre seront enrichies</li>
                                    <li>Les notes seront fusionnees</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 sticky bottom-0 bg-white dark:bg-gray-900">
                    <x-filament::button wire:click="closeMergeModal" color="gray">Annuler</x-filament::button>
                    <x-filament::button
                        wire:click="executeMerge"
                        wire:confirm="Etes-vous sur de vouloir fusionner ces comptes ? Cette action est irreversible."
                        color="primary"
                        icon="heroicon-o-arrows-pointing-in"
                        :disabled="!$selectedMasterId"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="executeMerge">Fusionner</span>
                        <span wire:loading wire:target="executeMerge">Fusion en cours...</span>
                    </x-filament::button>
                </div>

            @else
                {{-- RESULTAT --}}
                <div class="p-6">
                    <div class="rounded-lg bg-success-50 dark:bg-success-500/10 border border-success-200 dark:border-success-500/20 p-4 mb-6">
                        <div class="flex items-center gap-3">
                            <x-heroicon-s-check-circle class="w-6 h-6 text-success-500" />
                            <p class="font-medium text-success-700 dark:text-success-300">
                                Fusion reussie - {{ count($mergeLog) }} operation(s) effectuee(s)
                            </p>
                        </div>
                    </div>

                    @if(count($mergeLog) > 0)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Journal des operations</h3>
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($mergeLog as $entry)
                                <li class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 flex items-start gap-2">
                                    <x-heroicon-s-chevron-right class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" />
                                    {{ $entry }}
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <x-filament::button wire:click="closeMergeModal" color="primary" icon="heroicon-o-check">Fermer</x-filament::button>
                </div>
            @endif
        </div>
    </div>
    @endif

</x-filament-panels::page>
