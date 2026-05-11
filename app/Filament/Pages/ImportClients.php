<?php
namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\ImportMapping;
use App\Services\ClientImportService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class ImportClients extends Page implements HasForms
{
    use InteractsWithForms, WithFileUploads;

    protected static ?string $navigationIcon   = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationGroup  = 'Clients';
    protected static ?string $navigationLabel  = 'Import clients';
    protected static ?int    $navigationSort   = 5;
    protected static string  $view             = 'filament.pages.import-clients';

    public int     $currentStep         = 1;
    public         $uploadedFile        = null;
    public ?int    $payerClientId       = null;
    public bool    $createSubscriptions = true;
    public string  $importMode          = 'import';
    public array   $fileHeaders         = [];
    public array   $fileRows            = [];
    public array   $columnMapping       = [];
    public array   $contentSuggestions  = [];
    public string  $filePath            = '';
    public int     $previewCount        = 20;
    public array   $analysisResults     = [];
    public array   $rowActions          = [];
    public array   $importStats         = [];
    public ?int    $selectedMappingId   = null;
    public string  $newMappingName      = '';

    private const MAX_FILE_SIZE_KB = 10240;
    private const ALLOWED_MIMES = [
        'text/csv', 'text/plain', 'application/csv',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function getTitle(): string { return 'Import de clients'; }
    public function mount(): void { $this->previewCount = 20; }

    public function processUpload(): void
    {
        if (!$this->uploadedFile) {
            Notification::make()->title('Sélectionnez un fichier')->danger()->send();
            return;
        }

        $this->validate([
            'uploadedFile' => [
                'required', 'file',
                'max:' . self::MAX_FILE_SIZE_KB,
                'mimes:csv,txt,xlsx,xls',
            ],
        ], [
            'uploadedFile.max'   => 'Le fichier ne doit pas dépasser 10 Mo.',
            'uploadedFile.mimes' => 'Seuls les formats CSV, TXT et Excel sont acceptés.',
        ]);

        $detectedMime = $this->uploadedFile->getMimeType();
        if (!in_array($detectedMime, self::ALLOWED_MIMES)) {
            Notification::make()->title('Type de fichier non autorisé')->body("Type détecté : {$detectedMime}")->danger()->send();
            return;
        }

        $extension = strtolower($this->uploadedFile->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'txt', 'xlsx', 'xls'])) {
            Notification::make()->title('Extension non autorisée')->danger()->send();
            return;
        }

        try {
            $path            = $this->uploadedFile->store('imports', 'local');
            $this->filePath  = $path;
            $service         = new ClientImportService();
            $parsed          = $service->parseFile(Storage::disk('local')->path($path));
            $this->fileHeaders = $parsed['headers'];
            $this->fileRows    = $parsed['rows'];

            if (count($this->fileRows) > 5000) {
                Storage::disk('local')->delete($path);
                Notification::make()->title('Fichier trop volumineux')->body('Maximum 5 000 lignes par import.')->danger()->send();
                return;
            }

            if ($this->selectedMappingId) {
                $saved = ImportMapping::find($this->selectedMappingId);
                $this->columnMapping = $saved ? $saved->mapping : $service->suggestMapping($this->fileHeaders);
            } else {
                $this->columnMapping = $service->suggestMapping($this->fileHeaders);
            }

            $this->contentSuggestions = $service->analyzeContent($this->fileRows, $this->columnMapping, $this->fileHeaders);
            $this->currentStep = 2;
            Notification::make()->title(count($this->fileRows) . ' lignes chargées')->success()->send();

        } catch (\Exception $e) {
            Notification::make()->title('Erreur')->body($e->getMessage())->danger()->send();
        }
    }

    public function applySuggestion(int $ci, string $field): void
    {
        $this->columnMapping[$ci] = $field;
        unset($this->contentSuggestions[$ci]);
    }

    public function saveMapping(): void
    {
        if (empty($this->newMappingName)) {
            Notification::make()->title('Nom requis')->warning()->send();
            return;
        }

        $name = strip_tags(trim($this->newMappingName));
        if (strlen($name) > 100) {
            Notification::make()->title('Nom trop long (max 100 caractères)')->warning()->send();
            return;
        }

        ImportMapping::create([
            'name'       => $name,
            'mapping'    => $this->columnMapping,
            'options'    => ['headers' => $this->fileHeaders],
            'created_by' => auth()->id(),
        ]);

        $this->newMappingName = '';
        Notification::make()->title('Mapping sauvegardé')->success()->send();
    }

    public function loadMapping(int $id): void
    {
        // ── Correction : les mappings sont partagés entre admins (lecture OK)
        // mais on vérifie que l'ID existe bien en base
        $s = ImportMapping::find($id);
        if ($s) {
            $this->columnMapping = $s->mapping;
            Notification::make()->title("Chargé : {$s->name}")->success()->send();
        } else {
            Notification::make()->title('Mapping introuvable')->danger()->send();
        }
    }

    public function deleteMapping(int $id): void
    {
        // Seul le créateur peut supprimer son mapping
        $mapping = ImportMapping::where('id', $id)
            ->where('created_by', auth()->id())
            ->first();

        if (!$mapping) {
            Notification::make()->title('Action non autorisée')->body('Vous ne pouvez supprimer que vos propres mappings.')->danger()->send();
            return;
        }

        $mapping->delete();
        Notification::make()->title('Mapping supprimé')->success()->send();
    }

    public function analyzeData(): void
    {
        $mapped = array_values($this->columnMapping);
        if (empty(array_intersect($mapped, ['email', 'last_name', 'siret', 'client_number', 'external_code']))) {
            Notification::make()->title('Mappez au moins un identifiant')->danger()->send();
            return;
        }

        $service               = new ClientImportService();
        $this->analysisResults = $service->detectDuplicates($this->fileRows, $this->columnMapping);
        $this->rowActions      = [];

        foreach ($this->analysisResults as $i => $r) {
            if ($this->importMode === 'update') {
                $this->rowActions[$i] = $r['has_conflict'] ? 'update' : 'skip';
                if ($r['has_conflict']) {
                    $this->rowActions["update_id_{$i}"] = $r['duplicates'][0]['client']->id;
                }
            } else {
                $this->rowActions[$i] = $r['has_conflict'] ? 'skip' : 'create';
            }
        }

        $this->currentStep = 3;
        Notification::make()->title('Analyse terminée')->success()->send();
    }

    public function executeImport(): void
    {
        // ── Correction : valider le payerClientId côté serveur ───────────────
        if ($this->payerClientId !== null) {
            $payer = Client::find($this->payerClientId);
            if (!$payer || !$payer->isActive()) {
                Notification::make()
                    ->title('Compte payeur invalide ou inactif')
                    ->danger()->send();
                return;
            }
        }

        $service = new ClientImportService();
        $actions = $this->rowActions;

        foreach ($this->analysisResults as $i => $r) {
            if (($actions[$i] ?? '') === 'update' && !empty($r['duplicates'])) {
                $actions["update_id_{$i}"] = $r['duplicates'][0]['client']->id;
            }
        }

        $this->importStats = $service->executeImport(
            $this->fileRows,
            $this->columnMapping,
            $actions,
            $this->payerClientId,
            auth()->id(),
            $this->importMode
        );

        if ($this->filePath) Storage::disk('local')->delete($this->filePath);

        $this->currentStep = 4;
        Notification::make()->title('Import terminé !')->success()->send();
    }

    public function setAllConflictsAction(string $action): void
    {
        foreach ($this->analysisResults as $i => $r) {
            if ($r['has_conflict']) {
                $this->rowActions[$i] = $action;
                if ($action === 'update' && !empty($r['duplicates'])) {
                    $this->rowActions["update_id_{$i}"] = $r['duplicates'][0]['client']->id;
                }
            }
        }
    }

    public function resetWizard(): void
    {
        $this->currentStep        = 1;
        $this->uploadedFile       = null;
        $this->fileHeaders        = [];
        $this->fileRows           = [];
        $this->columnMapping      = [];
        $this->contentSuggestions = [];
        $this->analysisResults    = [];
        $this->rowActions         = [];
        $this->importStats        = [];
        $this->filePath           = '';
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->currentStep) $this->currentStep = $step;
    }

    public function getSavedMappingsProperty()
    {
        return ImportMapping::orderByDesc('created_at')->get();
    }
}
