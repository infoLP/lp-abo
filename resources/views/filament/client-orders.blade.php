@php
    $client = $getRecord();
    $orders = $client
        ? \App\Models\Order::with(['lines.magazine', 'beneficiary'])
            ->where(function($q) use ($client) {
                $q->where('client_id', $client->id)
                  ->orWhere('beneficiary_id', $client->id);
            })
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get()
        : collect();
@endphp

<div class="space-y-3">

    {{-- En-tête --}}
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Commandes ({{ $orders->count() }})
        </h3>
        @if($client?->id)
        <a href="{{ route('filament.admin.resources.orders.create', ['payer_id' => $client->id]) }}"
           class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium
                  bg-primary-600 text-white hover:bg-primary-500 transition shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouvelle commande
        </a>
        @endif
    </div>

    @if($orders->isEmpty())
        <div class="text-center py-8 text-sm text-gray-400 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
            Aucune commande enregistrée.
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">N° Commande</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Date</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Publications</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Bénéficiaire</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300">Total TTC</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300">Statut</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($orders as $order)
                        @php
                            $statusVal = is_string($order->status) ? $order->status : $order->status->value;
                            $statusColors = [
                                'brouillon' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                'validee'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                'installee' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                'annulee'   => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                            ];
                            $statusLabels = [
                                'brouillon' => 'Brouillon',
                                'validee'   => 'Validée',
                                'installee' => 'Installée',
                                'annulee'   => 'Annulée',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">

                            {{-- N° commande --}}
                            <td class="px-3 py-2 font-mono text-xs font-semibold text-gray-800 dark:text-gray-200">
                                {{ $order->number }}
                            </td>

                            {{-- Date --}}
                            <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ $order->order_date?->format('d/m/Y') }}
                            </td>

                            {{-- Publications --}}
                            <td class="px-3 py-2 text-xs">
                                @foreach($order->lines as $line)
                                    <span class="inline-block bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300
                                                  px-1.5 py-0.5 rounded text-xs mr-1 mb-0.5">
                                        {{ $line->magazine->short_name ?? $line->magazine->name ?? '—' }}
                                    </span>
                                @endforeach
                            </td>

                            {{-- Bénéficiaire --}}
                            <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-400">
                                @if($order->beneficiary_id && $order->beneficiary_id !== $order->client_id)
                                    {{ $order->beneficiary?->display_name ?? '—' }}
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>

                            {{-- Total TTC --}}
                            <td class="px-3 py-2 text-xs font-semibold text-right tabular-nums">
                                {{ number_format($order->total_ttc, 2, ',', ' ') }} €
                            </td>

                            {{-- Statut --}}
                            <td class="px-3 py-2 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                             {{ $statusColors[$statusVal] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $statusLabels[$statusVal] ?? $statusVal }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-3 py-2">
                                <div class="flex items-center justify-center gap-0.5">

                                    {{-- Modifier --}}
                                    <a href="{{ route('filament.admin.resources.orders.edit', $order) }}"
                                       title="Modifier la commande"
                                       class="p-1.5 rounded text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>

                                    {{-- Valider (seulement si brouillon) --}}
                                    @if($statusVal === 'brouillon')
                                    <button type="button"
                                        wire:click="changeOrderStatus({{ $order->id }}, 'validee')"
                                        wire:confirm="Valider la commande {{ $order->number }} ?"
                                        title="Valider la commande"
                                        class="p-1.5 rounded text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>
                                    @endif

                                    {{-- Installer (seulement si validée) --}}
                                    @if($statusVal === 'validee')
                                    <button type="button"
                                        wire:click="changeOrderStatus({{ $order->id }}, 'installee')"
                                        wire:confirm="Installer la commande {{ $order->number }} ? Cela créera les abonnements."
                                        title="Installer la commande"
                                        class="p-1.5 rounded text-gray-400 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                    @endif

                                    {{-- Annuler (brouillon ou validée) --}}
                                    @if(in_array($statusVal, ['brouillon', 'validee']))
                                    <button type="button"
                                        wire:click="changeOrderStatus({{ $order->id }}, 'annulee')"
                                        wire:confirm="Annuler la commande {{ $order->number }} ?"
                                        title="Annuler la commande"
                                        class="p-1.5 rounded text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                    @endif

                                    {{-- Remettre en brouillon (si annulée) --}}
                                    @if($statusVal === 'annulee')
                                    <button type="button"
                                        wire:click="changeOrderStatus({{ $order->id }}, 'brouillon')"
                                        wire:confirm="Remettre en brouillon la commande {{ $order->number }} ?"
                                        title="Remettre en brouillon"
                                        class="p-1.5 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </button>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
