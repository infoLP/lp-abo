<x-filament-panels::page>

    <x-filament::section>
        <x-slot name="heading">Liste des clients</x-slot>
        <x-slot name="description">
            Définissez le tri par défaut à l'ouverture de la liste clients.
            Les colonnes visibles sont mémorisées automatiquement par le navigateur.
        </x-slot>

        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-6 flex justify-end">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Enregistrer mes préférences
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    {{-- Info sur les colonnes ────────────────────────────────────────────── --}}
    <x-filament::section class="mt-4">
        <x-slot name="heading">Colonnes visibles</x-slot>
        <x-slot name="description">
            La visibilité des colonnes est configurable directement depuis la liste clients.
        </x-slot>

        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
            <p>
                Dans la liste clients, cliquez sur le bouton
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">
                    <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="w-3.5 h-3.5" />
                    Colonnes
                </span>
                en haut à droite du tableau pour afficher ou masquer des colonnes.
            </p>
            <p>Vos choix sont mémorisés automatiquement dans votre navigateur.</p>

            <div class="mt-3 grid grid-cols-2 md:grid-cols-3 gap-2">
                @foreach([
                    'N° Client'      => 'visible par défaut',
                    'Statut'         => 'visible par défaut',
                    'Société'        => 'visible par défaut',
                    'Contact'        => 'visible par défaut',
                    'Email'          => 'visible par défaut',
                    'CP'             => 'visible par défaut',
                    'Ville'          => 'visible par défaut',
                    'Payeur'         => 'visible par défaut',
                    'Abonnements'    => 'visible par défaut',
                    'Code ext.'      => 'masquée par défaut',
                    'Type'           => 'masquée par défaut',
                    'Téléphone'      => 'masquée par défaut',
                    'Pays'           => 'masquée par défaut',
                    'Date création'  => 'masquée par défaut',
                ] as $col => $defaut)
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-2 h-2 rounded-full {{ str_contains($defaut, 'masquée') ? 'bg-gray-300' : 'bg-green-400' }}"></span>
                        <span class="font-medium">{{ $col }}</span>
                        <span class="text-gray-400">({{ $defaut }})</span>
                    </div>
                @endforeach
            </div>
        </div>
    </x-filament::section>

</x-filament-panels::page>
