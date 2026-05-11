<?php

namespace App\Filament\Pages;

use App\Models\AccountingAssignment;
use App\Models\AccountingCode;
use App\Models\AnalyticalSection;
use App\Models\AuxiliaryCode;
use App\Models\Magazine;
use App\Models\VatRate;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class ComptabiliteSettings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Plan comptable';
    protected static ?string $navigationGroup = 'Paramètres';
    protected static ?string $title           = 'Paramétrage comptable';
    protected static ?int    $navigationSort  = 50;
    protected static ?string $slug            = 'plan-comptable';
    protected static string  $view            = 'filament.pages.comptabilite-settings';

    public string $activeTab = 'codes';

    public function table(Table $table): Table
    {
        return match ($this->activeTab) {
            'tva'          => $this->buildVatRatesTable($table),
            'affectations' => $this->buildAssignmentsTable($table),
            'auxiliaires'  => $this->buildAuxiliaryTable($table),
            'analytiques'  => $this->buildAnalyticalTable($table),
            default        => $this->buildCodesTable($table),
        };
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    // ── Codes comptables ──────────────────────────────────────────────────────
    private function buildCodesTable(Table $table): Table
    {
        return $table
            ->query(AccountingCode::query()->orderBy('code'))
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()->sortable()
                    ->weight('bold')->fontFamily('mono')
                    ->formatStateUsing(fn($state) => filled($state) ? (substr(str_replace(' ', '', $state), 0, -3) . ' ' . substr(str_replace(' ', '', $state), -3)) : '—'),
                Tables\Columns\TextColumn::make('label')
                    ->label('Libellé')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')->placeholder('—')->color('gray'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')->badge()
                    ->color(fn(string $state) => match ($state) {
                        'vente'     => 'success',
                        'tva'       => 'warning',
                        'livraison' => 'info',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => AccountingCode::types()[$state] ?? $state),
                Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter un code')
                    ->icon('heroicon-o-plus')
                    ->model(AccountingCode::class)
                    ->form($this->accountingCodeForm()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->form($this->accountingCodeForm()),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    private function accountingCodeForm(): array
    {
        return [
            TextInput::make('code')->label('Code comptable')
                ->required()->maxLength(20)->placeholder('707001'),
            TextInput::make('label')->label('Libellé')->required(),
            TextInput::make('description')->label('Description'),
            Select::make('type')->label('Type')
                ->options(AccountingCode::types())->required(),
            Toggle::make('is_active')->label('Actif')->default(true),
        ];
    }

    // ── Taux de TVA ───────────────────────────────────────────────────────────
    private function buildVatRatesTable(Table $table): Table
    {
        $zoneColumns = [];
        foreach (VatRate::ZONES as $key => $label) {
            $zoneColumns[] = Tables\Columns\TextColumn::make("{$key}_accounting_code")
                ->label($label)
                ->fontFamily('mono')
                ->placeholder('—')
                ->formatStateUsing(fn($state) => filled($state) ? (substr(str_replace(' ', '', $state), 0, -3) . ' ' . substr(str_replace(' ', '', $state), -3)) : '—')
                ->description(fn($record) => $record->{"{$key}_rate"}
                    ? $record->{"{$key}_rate"} . ' %' : null);
        }

        return $table
            ->query(VatRate::query()->orderBy('sort_order'))
            ->columns(array_merge([
                Tables\Columns\TextColumn::make('name')
                    ->label('Désignation')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('usage')
                    ->label('Usage')->color('gray'),
            ], $zoneColumns))
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter un taux')
                    ->model(VatRate::class)
                    ->form($this->vatRateForm()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->form($this->vatRateForm()),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    private function vatRateForm(): array
    {
        $fields = [
            TextInput::make('name')->label('Désignation du taux')->required(),
            Select::make('usage')->label('Usage de la taxe')
                ->options([
                    'Incluse dans les prix' => 'Incluse dans les prix',
                    'En sus des prix'       => 'En sus des prix',
                ])
                ->default('Incluse dans les prix')->required(),
        ];

        foreach (VatRate::ZONES as $key => $label) {
            $fields[] = Section::make($label)->columns(2)->compact()->schema([
                TextInput::make("{$key}_accounting_code")
                    ->label('Code compte')->maxLength(20)->placeholder('445720'),
                TextInput::make("{$key}_rate")
                    ->label('Taux (%)')->numeric()
                    ->minValue(0)->maxValue(100)->placeholder('2.10'),
            ]);
        }

        $fields[] = TextInput::make('sort_order')->label('Ordre')->numeric()->default(0);

        return $fields;
    }

    // ── Affectations comptables ───────────────────────────────────────────────
    private function buildAssignmentsTable(Table $table): Table
    {
        $zoneColumns = [];
        foreach (VatRate::ZONES as $key => $label) {
            $zoneColumns[] = Tables\Columns\TextColumn::make("{$key}_accounting_code")
                ->label($label)
                ->fontFamily('mono')
                ->placeholder('—')
                ->formatStateUsing(fn($state) => filled($state) ? (substr(str_replace(' ', '', $state), 0, -3) . ' ' . substr(str_replace(' ', '', $state), -3)) : '—');
        }

        return $table
            ->query(AccountingAssignment::query()->with(['magazine','vatRate'])->orderBy('sort_order'))
            ->columns(array_merge([
                Tables\Columns\TextColumn::make('label')
                    ->label('Affectation')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('type')->label('Type')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'abonnement' => 'success',
                        'revue'      => 'info',
                        'livraison'  => 'warning',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => AccountingAssignment::types()[$state] ?? $state),
                Tables\Columns\TextColumn::make('vatRate.name')
                    ->label('Taux TVA')->placeholder('—'),
            ], $zoneColumns))
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter une affectation')
                    ->model(AccountingAssignment::class)
                    ->form($this->assignmentForm()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->form($this->assignmentForm()),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    private function assignmentForm(): array
    {
        $fields = [
            TextInput::make('label')->label('Libellé')->required()
                ->placeholder('Vente Abonnement EJG'),
            Select::make('type')->label('Type')
                ->options(AccountingAssignment::types())->required(),
            Select::make('magazine_id')->label('Publication')
                ->options(Magazine::pluck('name', 'id'))->searchable()->nullable(),
            Select::make('vat_rate_id')->label('Taux TVA')
                ->options(VatRate::where('is_active', true)->pluck('name', 'id'))
                ->searchable()->nullable(),
        ];

        foreach (VatRate::ZONES as $key => $label) {
            $fields[] = TextInput::make("{$key}_accounting_code")
                ->label("Code — {$label}")->maxLength(20)->placeholder('707001');
        }

        $fields[] = TextInput::make('sort_order')->label('Ordre')->numeric()->default(0);

        return $fields;
    }

    // ── Codes auxiliaires ─────────────────────────────────────────────────────
    private function buildAuxiliaryTable(Table $table): Table
    {
        $form = [
            TextInput::make('code')->label('Code')->required()->maxLength(20),
            TextInput::make('label')->label('Libellé')->required(),
            TextInput::make('description')->label('Description'),
            Select::make('magazine_id')->label('Publication liée')
                ->options(Magazine::pluck('name', 'id'))->nullable(),
        ];

        return $table
            ->query(AuxiliaryCode::query()->with('magazine')->orderBy('code'))
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')->searchable()->sortable()
                    ->weight('bold')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('label')
                    ->label('Libellé')->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')->placeholder('—')->color('gray'),
                Tables\Columns\TextColumn::make('magazine.name')
                    ->label('Publication')->placeholder('—')->badge(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter un code')
                    ->model(AuxiliaryCode::class)
                    ->form($form),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->form($form),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    // ── Sections analytiques ──────────────────────────────────────────────────
    private function buildAnalyticalTable(Table $table): Table
    {
        $form = [
            TextInput::make('code')->label('Code')->required()->maxLength(20),
            TextInput::make('label')->label('Libellé')->required(),
            TextInput::make('description')->label('Description'),
            Toggle::make('is_active')->label('Actif')->default(true),
            TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
        ];

        return $table
            ->query(AnalyticalSection::query()->orderBy('code'))
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')->searchable()->fontFamily('mono')->weight('bold'),
                Tables\Columns\TextColumn::make('label')
                    ->label('Libellé')->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')->placeholder('—')->color('gray'),
                Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter une section')
                    ->model(AnalyticalSection::class)
                    ->form($form),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->form($form),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
