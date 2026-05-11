<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex items-center justify-between">

            {{-- Bouton Aperçu PDF (ouvre dans un nouvel onglet) --}}
            <a
                href="{{ route('admin.preview.invoice.pdf') }}"
                target="_blank"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300
                       bg-white text-gray-700 text-sm font-medium shadow-sm
                       hover:bg-gray-50 transition"
            >
                <x-heroicon-o-eye class="w-5 h-5 text-gray-500" />
                Aperçu facture PDF
            </a>

            {{-- Bouton Enregistrer --}}
            <x-filament::button type="submit" size="lg" icon="heroicon-o-check">
                Enregistrer les paramètres
            </x-filament::button>

        </div>
    </form>
</x-filament-panels::page>
