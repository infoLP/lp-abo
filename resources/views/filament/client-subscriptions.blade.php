{{-- ============================================================
     Onglet Abonnements — fiche client
     Relations réelles : magazine(), subscriptionPlan(), client(), payer()
     ============================================================ --}}

{{-- ── Bloc bénéficiaires (clients dont ce compte est payeur) ── --}}
@php
    $beneficiaries = $record->beneficiaries ?? collect();
@endphp

@if($beneficiaries->isNotEmpty())
<div class="mb-6" x-data="{ openBenef: false }">

    <button
        type="button"
        @click="openBenef = !openBenef"
        class="flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition"
    >
        <svg x-show="!openBenef" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
        <svg x-show="openBenef" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
        </svg>
        <span x-text="openBenef ? 'Masquer les bénéficiaires' : 'Afficher les bénéficiaires'"></span>
        <span class="ml-1 inline-flex items-center justify-center rounded-full bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-900 dark:text-primary-300">
            {{ $beneficiaries->count() }}
        </span>
    </button>

    <div x-show="openBenef" x-collapse class="mt-3 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Bénéficiaire</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Email</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Abonnements actifs</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-900">
                @foreach($beneficiaries as $benef)
                <tr>
                    <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">
                        {{ $benef->full_name ?: $benef->company_name }}
                    </td>
                    <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                        {{ $benef->email ?? '—' }}
                    </td>
                    <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                        {{ $benef->subscriptions()->where('status', 'active')->count() }}
                    </td>
                    <td class="px-4 py-2">
                        <a href="{{ route('filament.admin.resources.clients.view', $benef) }}"
                           class="text-primary-600 hover:underline dark:text-primary-400 text-xs">
                            Voir la fiche →
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── Tableau des abonnements ── --}}
@php
    $subscriptions = $record->subscriptions()
        ->with(['magazine', 'subscriptionPlan', 'payer'])
        ->latest()
        ->get();
@endphp

<div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Publication</th>
                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Formule</th>
                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Support</th>
                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Payeur</th>
                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Début</th>
                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Fin / Nº restants</th>
                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Statut</th>
                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-900">
            @forelse($subscriptions as $sub)
            <tr>
                {{-- Publication --}}
                <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">
                    {{ $sub->magazine?->name ?? '—' }}
                </td>

                {{-- Formule --}}
                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                    {{ $sub->subscriptionPlan?->name ?? '—' }}
                </td>

                {{-- Support --}}
                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                    {{ $sub->support_type instanceof \App\Enums\SupportType
                        ? $sub->support_type->label()
                        : ($sub->support_type ?? '—') }}
                </td>

                {{-- Payeur — affiché seulement si différent du client --}}
                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                    @if($sub->payer_client_id && $sub->payer_client_id !== $sub->client_id)
                        <a href="{{ route('filament.admin.resources.clients.view', $sub->payer) }}"
                           class="text-primary-600 hover:underline dark:text-primary-400">
                            {{ $sub->payer?->full_name ?: $sub->payer?->company_name ?? '—' }}
                        </a>
                    @else
                        <span class="text-gray-400 dark:text-gray-600">—</span>
                    @endif
                </td>

                {{-- Début --}}
                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                    {{ $sub->start_date?->format('d/m/Y') ?? '—' }}
                </td>

                {{-- Fin ou numéros restants --}}
                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                    @php
                        $modeVal = $sub->mode instanceof \App\Enums\SubscriptionMode
                            ? $sub->mode->value : $sub->mode;
                    @endphp
                    @if($modeVal === 'issues')
                        {{ $sub->remaining_issues ?? 0 }} nº
                    @else
                        {{ $sub->end_date?->format('d/m/Y') ?? '—' }}
                    @endif
                </td>

                {{-- Statut --}}
                <td class="px-4 py-2">
                    @php
                        $statusColors = [
                            'active'    => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                            'expired'   => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                            'suspended' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
                            'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
                            'pending'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                            'trial'     => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
                        ];
                        $statusVal = $sub->status instanceof \App\Enums\SubscriptionStatus
                            ? $sub->status->value : $sub->status;
                        $statusLabel = $sub->status instanceof \App\Enums\SubscriptionStatus
                            ? $sub->status->label() : $sub->status;
                        $statusColor = $statusColors[$statusVal] ?? 'bg-gray-100 text-gray-600';
                    @endphp
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusColor }}">
                        {{ $statusLabel }}
                    </span>
                </td>

                {{-- Actions --}}
                <td class="px-4 py-2">
                    <a href="{{ route('filament.admin.resources.subscriptions.edit', $sub) }}"
                       class="text-primary-600 hover:underline dark:text-primary-400 text-xs">
                        Modifier
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-6 text-center text-gray-400 dark:text-gray-600 italic">
                    Aucun abonnement enregistré.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
