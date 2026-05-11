@php
    $client = $getRecord();
    $addresses = $client ? $client->addresses()->orderByDesc('is_default')->orderBy('name')->get() : collect();
@endphp

<div x-data="{
    showModal: false,
    editMode: false,
    form: {
        id: null, name: '', address_type: 'particulier', usage: 'both', is_default: false,
        l1: '', l2: '', l3: '', l4: '', l5: '',
        l6_postal_code: '', l6_city: '', l6_cedex: '', l6_state_code: '', l7_country: 'FR'
    },
    openCreate() {
        this.editMode = false;
        this.form = {
            id: null,
            name: 'Adresse n°{{ $addresses->count() + 1 }}',
            address_type: '{{ $client?->type === "company" ? "entreprise" : "particulier" }}',
            usage: 'both', is_default: false,
            l1: '', l2: '', l3: '', l4: '', l5: '',
            l6_postal_code: '', l6_city: '', l6_cedex: '', l6_state_code: '', l7_country: 'FR'
        };
        this.showModal = true;
    },
    openEdit(addr) {
        this.editMode = true;
        this.form = { ...addr };
        this.showModal = true;
    },
    save() {
        $wire.saveAddress(this.form);
        this.showModal = false;
    }
}">

    {{-- ═══════════════════════════════════════
         LISTE DES ADRESSES
    ═══════════════════════════════════════ --}}
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Adresses ({{ $addresses->count() }})
        </h3>
        @if($client?->id)
        <button type="button" @click="openCreate()"
            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium
                   bg-primary-600 text-white hover:bg-primary-500 transition shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Ajouter une adresse
        </button>
        @endif
    </div>

    @if($client?->id)
        @forelse($addresses as $address)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 shadow-sm mb-2">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $address->name }}</span>
                        @if($address->is_default)
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium bg-primary-100 text-primary-700">Par défaut</span>
                        @endif
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $address->usage === 'billing' ? 'bg-blue-100 text-blue-700' : ($address->usage === 'delivery' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ $address->usage === 'billing' ? 'Facturation' : ($address->usage === 'delivery' ? 'Livraison' : 'Fact. + Livr.') }}
                        </span>
                        @if(!is_null($address->rnvp_valid))
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $address->rnvp_valid ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                {{ $address->rnvp_valid ? '✓ RNVP' : '✗ RNVP' }}
                            </span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-300 font-mono leading-5">
                        @foreach(array_filter([$address->l1, $address->l2, $address->l3, $address->l4, $address->l5]) as $line)
                            <div>{{ strtoupper($line) }}</div>
                        @endforeach
                        <div>{{ implode(' ', array_filter([$address->l6_postal_code, strtoupper($address->l6_city ?? ''), $address->l6_cedex ? 'CEDEX '.$address->l6_cedex : null])) }}</div>
                        @if($address->l7_country && $address->l7_country !== 'FR')<div>{{ $address->l7_country }}</div>@endif
                    </div>
                </div>
                <div class="flex items-center gap-0.5 flex-shrink-0">
                    <a href="/admin/subscriptions?tableFilters[address_id][value]={{ $address->id }}" target="_blank"
                       title="Abonnements" class="p-1.5 rounded text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 12h6m-6-4h6"/></svg>
                    </a>
                    <button type="button" title="Modifier"
                        @click="openEdit({{ json_encode(['id'=>$address->id,'name'=>$address->name,'address_type'=>$address->address_type,'usage'=>$address->usage,'is_default'=>(bool)$address->is_default,'l1'=>$address->l1??'','l2'=>$address->l2??'','l3'=>$address->l3??'','l4'=>$address->l4??'','l5'=>$address->l5??'','l6_postal_code'=>$address->l6_postal_code??'','l6_city'=>$address->l6_city??'','l6_cedex'=>$address->l6_cedex??'','l6_state_code'=>$address->l6_state_code??'','l7_country'=>$address->l7_country??'FR']) }})"
                        class="p-1.5 rounded text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button type="button" title="Supprimer"
                        wire:click="deleteAddress({{ $address->id }})"
                        wire:confirm="Supprimer cette adresse ?"
                        class="p-1.5 rounded text-gray-400 hover:text-red-600 hover:bg-red-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    <button type="button" title="Vérifier RNVP"
                        wire:click="checkRnvp({{ $address->id }})"
                        class="p-1.5 rounded text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-8 text-sm text-gray-400 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
            Aucune adresse enregistrée.
        </div>
        @endforelse
    @else
        <div class="text-center py-8 text-sm text-gray-400 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
            Enregistrez d'abord le client pour gérer ses adresses.
        </div>
    @endif

    {{-- ═══════════════════════════════════════
         MODAL — teleport sur body
    ═══════════════════════════════════════ --}}
    <template x-teleport="body">
        <div x-show="showModal"
             x-cloak
             @keydown.escape.window="showModal = false"
             style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;box-sizing:border-box">

            {{-- Overlay --}}
            <div @click="showModal = false"
                 style="position:absolute;inset:0;background:rgba(0,0,0,0.6)"></div>

            {{-- Panneau --}}
            <div @click.stop
                 style="position:relative;background:white;border-radius:0.75rem;box-shadow:0 25px 50px rgba(0,0,0,0.25);width:100%;max-width:680px;display:flex;flex-direction:column;max-height:88vh;overflow:hidden;margin:auto">

                {{-- Header --}}
                <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-bottom:1px solid #e5e7eb;flex-shrink:0">
                    <h2 style="font-size:1rem;font-weight:600;color:#111827;display:flex;align-items:center;gap:0.5rem">
                        <svg style="width:1.25rem;height:1.25rem;color:#6366f1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span x-text="editMode ? 'Modifier l\'adresse' : 'Nouvelle adresse'"></span>
                    </h2>
                    <button @click="showModal = false" type="button"
                        style="padding:0.25rem;border-radius:0.5rem;color:#9ca3af;border:none;background:none;cursor:pointer"
                        onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                        <svg style="width:1.25rem;height:1.25rem" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Corps scrollable --}}
                <div style="flex:1;overflow-y:auto;padding:1.25rem 1.5rem">

                    {{-- Nom / Type / Usage / Défaut --}}
                    <div style="display:grid;grid-template-columns:2fr 1.5fr 1.5fr 1fr;gap:0.75rem;margin-bottom:1rem">
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:0.25rem">Nom <span style="color:#ef4444">*</span></label>
                            <input type="text" x-model="form.name" placeholder="Adresse principale"
                                style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.4rem 0.6rem;font-size:0.85rem;outline:none;box-sizing:border-box">
                        </div>
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:0.25rem">Type</label>
                            <select x-model="form.address_type"
                                style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.4rem 0.6rem;font-size:0.85rem;outline:none;box-sizing:border-box">
                                <option value="particulier">Particulier</option>
                                <option value="entreprise">Entreprise</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:0.25rem">Usage</label>
                            <select x-model="form.usage"
                                style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.4rem 0.6rem;font-size:0.85rem;outline:none;box-sizing:border-box">
                                <option value="both">Fact. + Livraison</option>
                                <option value="billing">Facturation</option>
                                <option value="delivery">Livraison</option>
                            </select>
                        </div>
                        <div style="display:flex;align-items:flex-end;padding-bottom:0.25rem">
                            <label style="display:flex;align-items:center;gap:0.4rem;cursor:pointer;font-size:0.8rem;color:#374151">
                                <input type="checkbox" x-model="form.is_default"
                                    style="width:1rem;height:1rem;border-radius:0.25rem;accent-color:#6366f1">
                                Par défaut
                            </label>
                        </div>
                    </div>

                    <hr style="border:none;border-top:1px solid #e5e7eb;margin-bottom:1rem">
                    <p style="font-size:0.7rem;color:#9ca3af;font-style:italic;margin-bottom:0.75rem">Format RNVP — 38 caractères max par ligne</p>

                    {{-- Champs L1–L5 --}}
                    @foreach([
                        ['key'=>'l1','label'=>'L1 *','required'=>true,'hint'=>'Destinataire','placeholder'=>'M. JEAN DUPONT'],
                        ['key'=>'l2','label'=>'L2','required'=>false,'hint'=>'Complément identification (appt, étage...)','placeholder'=>'APPT 12 — ÉTAGE 3'],
                        ['key'=>'l3','label'=>'L3','required'=>false,'hint'=>'Complément distribution (résidence, bât...)','placeholder'=>'RÉSIDENCE LES PINS — BÂT A'],
                        ['key'=>'l4','label'=>'L4 *','required'=>true,'hint'=>'Numéro + libellé de voie','placeholder'=>'108 RUE FONDAUDÈGE'],
                        ['key'=>'l5','label'=>'L5','required'=>false,'hint'=>'Lieu-dit / boîte postale','placeholder'=>'LIEU-DIT LE MOULIN — BP 123'],
                    ] as $f)
                    <div style="display:grid;grid-template-columns:3rem 1fr;gap:0.5rem;align-items:start;margin-bottom:0.6rem">
                        <div style="text-align:right;padding-top:0.35rem">
                            <span style="font-size:0.7rem;font-weight:700;color:{{ $f['required'] ? '#6366f1' : '#9ca3af' }}">{{ $f['label'] }}</span>
                        </div>
                        <div>
                            <input type="text" x-model="form.{{ $f['key'] }}" maxlength="38"
                                placeholder="{{ $f['placeholder'] }}"
                                style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.4rem 0.6rem;font-size:0.82rem;font-family:monospace;background:{{ $f['required'] ? '#eff6ff' : 'white' }};outline:none;box-sizing:border-box">
                            <span style="font-size:0.65rem;color:#9ca3af">{{ $f['hint'] }}</span>
                        </div>
                    </div>
                    @endforeach

                    {{-- L6 --}}
                    <div style="display:grid;grid-template-columns:3rem 1fr;gap:0.5rem;align-items:start;margin-bottom:0.6rem">
                        <div style="text-align:right;padding-top:0.35rem">
                            <span style="font-size:0.7rem;font-weight:700;color:#6366f1">L6 *</span>
                        </div>
                        <div style="display:grid;grid-template-columns:100px 1fr 90px;gap:0.4rem">
                            <div>
                                <input type="text" x-model="form.l6_postal_code" maxlength="10" placeholder="33000"
                                    style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.4rem 0.6rem;font-size:0.82rem;font-family:monospace;background:#eff6ff;outline:none;box-sizing:border-box">
                                <span style="font-size:0.65rem;color:#9ca3af">Code postal</span>
                            </div>
                            <div>
                                <input type="text" x-model="form.l6_city" maxlength="32" placeholder="BORDEAUX"
                                    style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.4rem 0.6rem;font-size:0.82rem;font-family:monospace;background:#eff6ff;outline:none;box-sizing:border-box">
                                <span style="font-size:0.65rem;color:#9ca3af">Ville</span>
                            </div>
                            <div>
                                <input type="text" x-model="form.l6_cedex" maxlength="10" placeholder="CEDEX"
                                    style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.4rem 0.6rem;font-size:0.82rem;font-family:monospace;background:white;outline:none;box-sizing:border-box">
                                <span style="font-size:0.65rem;color:#9ca3af">CEDEX</span>
                            </div>
                        </div>
                    </div>

                    {{-- L7 --}}
                    <div style="display:grid;grid-template-columns:3rem 1fr;gap:0.5rem;align-items:start;margin-bottom:1rem">
                        <div style="text-align:right;padding-top:0.35rem">
                            <span style="font-size:0.7rem;font-weight:700;color:#6366f1">L7</span>
                        </div>
                        <div>
                            <select x-model="form.l7_country"
                                style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.4rem 0.6rem;font-size:0.82rem;background:#eff6ff;outline:none;box-sizing:border-box">
                                <option value="FR">France</option>
                                <option value="BE">Belgique</option>
                                <option value="CH">Suisse</option>
                                <option value="LU">Luxembourg</option>
                                <option value="MC">Monaco</option>
                                <option value="DE">Allemagne</option>
                                <option value="ES">Espagne</option>
                                <option value="IT">Italie</option>
                                <option value="GB">Royaume-Uni</option>
                                <option value="US">États-Unis</option>
                            </select>
                            <span style="font-size:0.65rem;color:#9ca3af">Pays (obligatoire uniquement hors France)</span>
                        </div>
                    </div>

                    {{-- Aperçu --}}
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0.5rem;padding:0.75rem">
                        <p style="font-size:0.7rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.5rem">Aperçu</p>
                        <div style="font-family:monospace;font-size:0.8rem;color:#374151;line-height:1.6">
                            <div x-show="form.l1" x-text="form.l1.toUpperCase()"></div>
                            <div x-show="form.l2" x-text="form.l2.toUpperCase()"></div>
                            <div x-show="form.l3" x-text="form.l3.toUpperCase()"></div>
                            <div x-show="form.l4" x-text="form.l4.toUpperCase()"></div>
                            <div x-show="form.l5" x-text="form.l5.toUpperCase()"></div>
                            <div x-show="form.l6_postal_code || form.l6_city"
                                 x-text="(form.l6_postal_code + ' ' + form.l6_city.toUpperCase() + (form.l6_cedex ? ' CEDEX ' + form.l6_cedex : '')).trim()"></div>
                            <div x-show="form.l7_country && form.l7_country !== 'FR'" x-text="form.l7_country"></div>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div style="display:flex;justify-content:flex-end;gap:0.75rem;padding:1rem 1.5rem;border-top:1px solid #e5e7eb;background:#f9fafb;border-radius:0 0 0.75rem 0.75rem;flex-shrink:0">
                    <button type="button" @click="showModal = false"
                        style="padding:0.5rem 1rem;border-radius:0.5rem;font-size:0.875rem;font-weight:500;border:1px solid #d1d5db;background:white;color:#374151;cursor:pointer"
                        onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                        Annuler
                    </button>
                    <button type="button" @click="save()"
                        style="padding:0.5rem 1.25rem;border-radius:0.5rem;font-size:0.875rem;font-weight:600;border:none;background:#6366f1;color:white;cursor:pointer"
                        onmouseover="this.style.background='#4f46e5'" onmouseout="this.style.background='#6366f1'">
                        <span x-text="editMode ? 'Enregistrer les modifications' : 'Créer l\'adresse'"></span>
                    </button>
                </div>

            </div>
        </div>
    </template>

</div>
