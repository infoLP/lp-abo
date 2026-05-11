<x-filament-panels::page>
<div class="flex items-center justify-center mb-8 gap-2">
@php $steps=[1=>'Fichier',2=>'Mapping',3=>'Verification',4=>'Resultat']; @endphp
@foreach($steps as $step=>$label)<div class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium @if($currentStep===$step) bg-primary-600 text-white @elseif($currentStep>$step) bg-primary-100 text-primary-700 @else bg-gray-100 text-gray-400 @endif"><span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold @if($currentStep===$step) bg-white text-primary-600 @elseif($currentStep>$step) bg-primary-600 text-white @else bg-gray-300 text-white @endif">@if($currentStep>$step)V@else{{ $step }}@endif</span>{{ $label }}</div>@if($step<4)<div class="w-8 h-0.5 @if($currentStep>$step)bg-primary-400 @else bg-gray-200 @endif"></div>@endif @endforeach</div>

@if($currentStep===1)
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 p-6 max-w-3xl mx-auto">
<h2 class="text-lg font-semibold mb-4">1. Configuration</h2><div class="space-y-6">
<div><label class="block text-sm font-medium mb-2">Mode</label><div class="flex gap-4"><label class="flex items-center gap-2 px-4 py-3 border rounded-lg cursor-pointer @if($importMode==='import')border-primary-500 bg-primary-50 ring-2 ring-primary-200 @else border-gray-300 @endif"><input type="radio" wire:model.live="importMode" value="import" class="text-primary-600"><div><span class="font-medium text-sm">Import</span><br><span class="text-xs text-gray-500">Creer clients</span></div></label><label class="flex items-center gap-2 px-4 py-3 border rounded-lg cursor-pointer @if($importMode==='update')border-blue-500 bg-blue-50 ring-2 ring-blue-200 @else border-gray-300 @endif"><input type="radio" wire:model.live="importMode" value="update" class="text-blue-600"><div><span class="font-medium text-sm">MAJ</span><br><span class="text-xs text-gray-500">Enrichir fiches</span></div></label></div></div>

{{-- Drag & Drop avec Livewire upload natif --}}
<div
    x-data="{
        dragging: false,
        hasFile: @entangle('uploadedFile').live ? true : false,
        uploading: false,
        progress: 0
    }"
    x-on:livewire-upload-start="uploading = true"
    x-on:livewire-upload-finish="uploading = false; hasFile = true"
    x-on:livewire-upload-error="uploading = false"
    x-on:livewire-upload-progress="progress = $event.detail.progress"
>
    <label class="block text-sm font-medium mb-2">Fichier CSV ou Excel</label>
    <div
        x-on:dragover.prevent="dragging = true"
        x-on:dragleave.prevent="dragging = false"
        x-on:drop.prevent="
            dragging = false;
            uploading = true;
            const file = $event.dataTransfer.files[0];
            if (file) {
                @this.upload('uploadedFile', file,
                    (name) => { uploading = false; hasFile = true; },
                    () => { uploading = false; },
                    (event) => { progress = event.detail.progress; }
                );
            }
        "
        :class="{
            'border-primary-500 bg-primary-50 scale-[1.01]': dragging,
            'border-green-400 bg-green-50': hasFile && !uploading && !dragging,
            'border-gray-300 bg-white hover:border-gray-400': !dragging && !hasFile && !uploading
        }"
        class="relative border-2 border-dashed rounded-xl p-8 text-center transition-all duration-200 cursor-pointer"
        x-on:click="$refs.fileInput.click()"
    >
        <input
            type="file"
            x-ref="fileInput"
            wire:model="uploadedFile"
            accept=".csv,.xlsx,.xls"
            class="hidden"
        >

        {{-- Etat : en attente --}}
        <div x-show="!dragging && !uploading && !hasFile" class="space-y-3">
            <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-700">Glissez-deposez votre fichier ici</p>
                <p class="text-xs text-gray-500 mt-1">ou <span class="text-primary-600 font-medium">cliquez pour parcourir</span></p>
            </div>
            <p class="text-xs text-gray-400">CSV (;) ou Excel (.xlsx)</p>
        </div>

        {{-- Etat : survol drag --}}
        <div x-show="dragging" x-cloak class="space-y-2 py-4">
            <div class="w-14 h-14 bg-primary-100 rounded-full flex items-center justify-center mx-auto animate-bounce">
                <svg class="w-7 h-7 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-primary-700">Deposez le fichier !</p>
        </div>

        {{-- Etat : upload en cours --}}
        <div x-show="uploading" x-cloak class="space-y-3 py-4">
            <svg class="w-10 h-10 text-primary-500 animate-spin mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-sm font-medium text-primary-700">Chargement...</p>
            <div class="w-48 bg-gray-200 rounded-full h-2 mx-auto">
                <div class="bg-primary-600 h-2 rounded-full transition-all" :style="'width: ' + progress + '%'"></div>
            </div>
        </div>

        {{-- Etat : fichier charge --}}
        <div x-show="hasFile && !uploading && !dragging" x-cloak class="space-y-2 py-2">
            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-green-700">Fichier pret</p>
            <p class="text-xs text-green-600">Cliquez ou deposez un autre fichier pour remplacer</p>
        </div>
    </div>
</div>

@if($this->savedMappings->count()>0)<div><label class="block text-sm font-medium mb-2">Mapping sauvegarde</label><select wire:model="selectedMappingId" class="w-full border rounded-lg px-4 py-2 text-sm"><option value="">-- Auto --</option>@foreach($this->savedMappings as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>@endif
<div><label class="block text-sm font-medium mb-2">Compte payeur</label><select wire:model="payerClientId" class="w-full border rounded-lg px-4 py-2 text-sm"><option value="">-- Aucun --</option>@foreach(\App\Models\Client::where('is_payer',true)->where('status','active')->get() as $p)<option value="{{ $p->id }}">{{ $p->client_number }} - {{ $p->full_name }}</option>@endforeach</select></div>
<div class="flex justify-end pt-4 border-t"><x-filament::button wire:click="processUpload" wire:loading.attr="disabled" icon="heroicon-o-arrow-right">Analyser</x-filament::button></div>
</div></div>
@endif

@if($currentStep===2)
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 p-6">
<div class="flex justify-between items-start mb-4"><div><h2 class="text-lg font-semibold">2. Mapping</h2><p class="text-sm text-gray-500">{{ count($fileRows) }} lignes</p></div><select wire:model.live="previewCount" class="border rounded px-2 py-1 text-sm"><option value="10">10</option><option value="20">20</option><option value="50">50</option><option value="100">100</option><option value="{{ count($fileRows) }}">Tout</option></select></div>
@if(count($contentSuggestions)>0)<div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4"><p class="text-sm font-semibold text-blue-800 mb-2">Suggestions :</p><div class="flex flex-wrap gap-2">@foreach($contentSuggestions as $ci=>$sug)<x-filament::button size="xs" color="info" wire:click="applySuggestion({{ $ci }},'{{ $sug['field'] }}')">{{ $fileHeaders[$ci]??'' }} = {{ \App\Services\ClientImportService::getAvailableFields()[$sug['field']]??'' }}</x-filament::button>@endforeach</div></div>@endif
<div class="overflow-x-auto mb-4 border rounded-lg" style="max-height:500px;overflow-y:auto"><table class="text-sm border-collapse w-full"><thead class="bg-gray-50 sticky top-0 z-10"><tr><th class="border px-2 py-1 text-xs w-10">#</th>@foreach($fileHeaders as $i=>$h)<th class="border px-2 py-2 text-left min-w-[180px]"><div class="font-medium text-xs mb-1 truncate">{{ $h }}</div><select wire:model.live="columnMapping.{{ $i }}" class="w-full border rounded px-1 py-0.5 text-xs @if(($columnMapping[$i]??'_skip')!=='_skip')bg-green-50 border-green-400 @endif">@foreach(\App\Services\ClientImportService::getAvailableFields() as $f=>$l)<option value="{{ $f }}">{{ $l }}</option>@endforeach</select></th>@endforeach</tr></thead><tbody>@foreach(array_slice($fileRows,0,$previewCount) as $ri=>$row)<tr class="hover:bg-gray-50"><td class="border px-2 py-1 text-xs text-gray-400 text-center">{{ $ri+2 }}</td>@foreach($fileHeaders as $i=>$h)<td class="border px-2 py-1 text-xs truncate max-w-[200px] @if(($columnMapping[$i]??'_skip')==='_skip')text-gray-300 @endif">{{ \Illuminate\Support\Str::limit($row[$i]??'',40) }}</td>@endforeach</tr>@endforeach</tbody></table></div>
<div class="bg-gray-50 rounded-lg p-4 mb-4"><div class="flex gap-2"><input type="text" wire:model="newMappingName" placeholder="Nom du mapping" class="flex-1 border rounded-lg px-3 py-1.5 text-sm"><x-filament::button size="sm" color="gray" wire:click="saveMapping">Sauvegarder</x-filament::button></div>@if($this->savedMappings->count()>0)<div class="mt-3 flex flex-wrap gap-2">@foreach($this->savedMappings as $m)<span class="inline-flex items-center gap-1 bg-white border rounded-full px-3 py-1 text-xs"><button wire:click="loadMapping({{ $m->id }})" class="text-primary-600 hover:underline">{{ $m->name }}</button><button wire:click="deleteMapping({{ $m->id }})" wire:confirm="Supprimer?" class="text-red-400 ml-1">x</button></span>@endforeach</div>@endif</div>
<div class="flex justify-between pt-4 border-t"><x-filament::button color="gray" wire:click="goToStep(1)" icon="heroicon-o-arrow-left">Retour</x-filament::button><x-filament::button wire:click="analyzeData" wire:loading.attr="disabled" icon="heroicon-o-arrow-right">Verifier doublons</x-filament::button></div>
</div>
@endif

@if($currentStep===3)
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 p-6">
@php $conflicts=collect($analysisResults)->where('has_conflict',true); $clean=collect($analysisResults)->where('has_conflict',false); $toCreate=0;$toUpdate=0;$toSkip=0; foreach($rowActions as $k=>$v){if(!is_string($v))continue;if(str_starts_with((string)$k,'update_id_'))continue;if($v==='create')$toCreate++;elseif($v==='update')$toUpdate++;elseif($v==='skip')$toSkip++;} $totalAction=$toCreate+$toUpdate; @endphp
<h2 class="text-lg font-semibold mb-2">3. Verification</h2>
<div class="flex flex-wrap gap-3 text-sm mb-4"><span class="px-3 py-1 bg-green-100 text-green-800 rounded-full">{{ $clean->count() }} nouveau(x)</span><span class="px-3 py-1 bg-amber-100 text-amber-800 rounded-full">{{ $conflicts->count() }} doublon(s)</span></div>
@if($conflicts->count()>0)<div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4"><div class="flex items-center justify-between mb-3"><p class="text-sm font-semibold text-amber-800">Doublons</p><div class="flex gap-2"><x-filament::button size="xs" color="success" wire:click="setAllConflictsAction('create')">Tout creer</x-filament::button><x-filament::button size="xs" color="info" wire:click="setAllConflictsAction('update')">Tout MAJ</x-filament::button><x-filament::button size="xs" color="gray" wire:click="setAllConflictsAction('skip')">Ignorer</x-filament::button></div></div><div class="overflow-x-auto max-h-80 overflow-y-auto"><table class="w-full text-sm"><thead class="bg-amber-100 sticky top-0"><tr><th class="px-2 py-1.5 text-left text-xs">L.</th><th class="px-2 py-1.5 text-left text-xs">Import</th><th class="px-2 py-1.5 text-left text-xs">Existant</th><th class="px-2 py-1.5 text-left text-xs">Match</th><th class="px-2 py-1.5 text-left text-xs w-28">Action</th></tr></thead><tbody class="divide-y">@foreach($conflicts as $index=>$result)<tr><td class="px-2 py-1.5 text-xs">{{ $index+2 }}</td><td class="px-2 py-1.5"><p class="text-xs font-medium">{{ $result['data']['last_name']??'' }} {{ $result['data']['first_name']??'' }}</p><p class="text-xs text-gray-500">{{ $result['data']['email']??'' }}</p></td><td class="px-2 py-1.5">@foreach($result['duplicates'] as $dup)<p class="text-xs font-medium">{{ $dup['client']->full_name }}</p><p class="text-xs text-gray-500">{{ $dup['client']->client_number }}</p>@endforeach</td><td class="px-2 py-1.5">@foreach($result['duplicates'] as $dup)<span class="px-1.5 py-0.5 bg-amber-200 text-amber-800 rounded text-xs">{{ $dup['match_type'] }}</span>@endforeach</td><td class="px-2 py-1.5"><select wire:model.live="rowActions.{{ $index }}" class="border rounded px-2 py-1 text-xs w-full"><option value="skip">Ignorer</option><option value="create">Creer</option><option value="update">MAJ</option></select></td></tr>@endforeach</tbody></table></div></div>@endif
@if($clean->count()>0&&$importMode!=='update')<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4"><p class="text-sm font-semibold text-green-800 mb-2">{{ $clean->count() }} nouveau(x)</p><div class="overflow-x-auto max-h-48 overflow-y-auto"><table class="w-full text-xs"><thead class="bg-green-100 sticky top-0"><tr><th class="px-2 py-1 text-left">L.</th><th class="px-2 py-1 text-left">Nom</th><th class="px-2 py-1 text-left">Email</th><th class="px-2 py-1 text-left">Ville</th></tr></thead><tbody class="divide-y">@foreach($clean->take(100) as $index=>$result)<tr><td class="px-2 py-1">{{ $index+2 }}</td><td class="px-2 py-1">{{ $result['data']['last_name']??'' }} {{ $result['data']['first_name']??'' }}</td><td class="px-2 py-1">{{ $result['data']['email']??'' }}</td><td class="px-2 py-1">{{ $result['data']['postal_code']??'' }} {{ $result['data']['city']??'' }}</td></tr>@endforeach</tbody></table></div></div>@endif
<div class="bg-gray-100 rounded-lg p-4 mb-6 text-center"><p class="text-sm font-semibold"><span class="text-green-700">{{ $toCreate }} creation(s)</span> - <span class="text-blue-700">{{ $toUpdate }} MAJ</span> - <span class="text-gray-500">{{ $toSkip }} ignore(s)</span></p></div>
<div class="flex justify-between pt-4 border-t"><x-filament::button color="gray" wire:click="goToStep(2)" icon="heroicon-o-arrow-left">Retour</x-filament::button>@if($totalAction>0)<x-filament::button color="success" size="lg" wire:click="executeImport" wire:loading.attr="disabled" wire:confirm="Confirmer?" icon="heroicon-o-check-circle">LANCER ({{ $totalAction }})</x-filament::button>@else<x-filament::button color="gray" size="lg" disabled>Aucun client</x-filament::button>@endif</div>
</div>
@endif

@if($currentStep===4)
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 p-6 max-w-2xl mx-auto">
<div class="text-center mb-6"><div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4"><x-heroicon-o-check-circle class="w-8 h-8 text-green-600"/></div><h2 class="text-2xl font-bold">Import termine !</h2></div>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6"><div class="bg-green-50 rounded-lg p-4 text-center"><p class="text-2xl font-bold text-green-700">{{ $importStats['created']??0 }}</p><p class="text-sm text-green-600">Crees</p></div><div class="bg-blue-50 rounded-lg p-4 text-center"><p class="text-2xl font-bold text-blue-700">{{ $importStats['updated']??0 }}</p><p class="text-sm text-blue-600">MAJ</p></div><div class="bg-gray-50 rounded-lg p-4 text-center"><p class="text-2xl font-bold text-gray-700">{{ $importStats['skipped']??0 }}</p><p class="text-sm text-gray-600">Ignores</p></div><div class="bg-purple-50 rounded-lg p-4 text-center"><p class="text-2xl font-bold text-purple-700">{{ $importStats['subscriptions_created']??0 }}</p><p class="text-sm text-purple-600">Abon.</p></div></div>
@if(!empty($importStats['errors']))<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6"><p class="text-sm font-semibold text-red-800 mb-2">Erreurs</p><ul class="text-xs text-red-700 space-y-1 max-h-48 overflow-y-auto">@foreach($importStats['errors'] as $e)<li>- {{ $e }}</li>@endforeach</ul></div>@endif
<div class="flex justify-center gap-4 pt-4 border-t"><x-filament::button wire:click="resetWizard" icon="heroicon-o-arrow-path">Nouvel import</x-filament::button><x-filament::button color="gray" tag="a" href="{{ route('filament.admin.resources.clients.index') }}" icon="heroicon-o-users">Voir clients</x-filament::button></div>
</div>
@endif
</x-filament-panels::page>
