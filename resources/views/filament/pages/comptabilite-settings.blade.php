<x-filament-panels::page>

    {{-- Onglets de navigation --}}
    <div class="flex gap-2 flex-wrap mb-6 border-b border-gray-200 dark:border-gray-700 pb-3">
        @php
            $tabs = [
                'codes'        => ['label' => 'Codes comptables',     'icon' => 'heroicon-o-hashtag'],
                'tva'          => ['label' => 'Comptes de TVA',       'icon' => 'heroicon-o-receipt-percent'],
                'affectations' => ['label' => 'Comptes de ventes',    'icon' => 'heroicon-o-banknotes'],
                'auxiliaires'  => ['label' => 'Codes auxiliaires',    'icon' => 'heroicon-o-users'],
                'analytiques'  => ['label' => 'Sections analytiques', 'icon' => 'heroicon-o-chart-bar'],
            ];
        @endphp

        @foreach($tabs as $key => $tab)
            <button
                wire:click="setTab('{{ $key }}')"
                @class([
                    'inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition',
                    'bg-primary-600 text-white shadow-sm'                                        => $activeTab === $key,
                    'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800' => $activeTab !== $key,
                ])
            >
                <x-filament::icon :icon="$tab['icon']" class="w-4 h-4" />
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Table --}}
    <div wire:key="table-{{ $activeTab }}">
        {{ $this->table }}
    </div>

</x-filament-panels::page>
