<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Client;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;

class InvoiceResource extends Resource
{
    protected static ?string $model            = Invoice::class;
    protected static ?string $navigationIcon   = 'heroicon-o-document-text';
    protected static ?string $navigationGroup  = 'Abonnements';
    protected static ?string $navigationLabel  = 'Factures';
    protected static ?string $modelLabel       = 'Facture';
    protected static ?string $pluralModelLabel = 'Factures';
    protected static ?int    $navigationSort   = 6;

    // ── Formulaire ─────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── En-tête ──────────────────────────────────────────────────────
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\TextInput::make('invoice_number')
                        ->label('N° Facture')
                        ->disabled()
                        ->placeholder('Généré automatiquement'),

                    Forms\Components\Select::make('status')
                        ->label('Statut')
                        ->options(collect(InvoiceStatus::cases())
                            ->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                        ->default(InvoiceStatus::Draft->value)
                        ->required(),

                    Forms\Components\DatePicker::make('invoice_date')
                        ->label('Date de facture')
                        ->default(now())
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $set('due_date', \Carbon\Carbon::parse($state)->addDays(30)->format('Y-m-d'));
                            }
                        }),

                    Forms\Components\DatePicker::make('due_date')
                        ->label('Date d\'échéance')
                        ->default(now()->addDays(30))
                        ->required(),
                ])
                ->columns(4),

            // ── Onglets ───────────────────────────────────────────────────────
            Forms\Components\Tabs::make('Onglets')->tabs([

                // ══ Onglet Client ═════════════════════════════════════════════
                Forms\Components\Tabs\Tab::make('Client')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Bénéficiaire')
                            ->options(fn() => Client::where('is_active', true)
                                ->orderBy('last_name')
                                ->get()
                                ->mapWithKeys(fn($c) => [
                                    $c->id => $c->display_name
                                        . ($c->client_number ? ' [' . $c->client_number . ']' : ''),
                                ]))
                            ->searchable()
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('payer_client_id')
                            ->label('Payeur (si différent)')
                            ->options(fn() => Client::where('is_active', true)
                                ->orderBy('last_name')
                                ->get()
                                ->mapWithKeys(fn($c) => [
                                    $c->id => $c->display_name
                                        . ($c->client_number ? ' [' . $c->client_number . ']' : ''),
                                ]))
                            ->searchable()
                            ->nullable()
                            ->placeholder('Identique au bénéficiaire')
                            ->columnSpan(1),
                    ])->columns(2),

                // ══ Onglet Lignes ═════════════════════════════════════════════
                Forms\Components\Tabs\Tab::make('Lignes')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([

                        TableRepeater::make('lines')
                            ->relationship('lines')
                            ->headers([
                                Header::make('Désignation')->width('50%'),
                                Header::make('Qté')->width('10%'),
                                Header::make('Prix unit. HT')->width('18%'),
                                Header::make('Total HT')->width('17%'),
                            ])
                            ->schema([
                                Forms\Components\TextInput::make('description')
                                    ->label('Désignation')
                                    ->required()
                                    ->columnSpan(4),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qté')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $set('total', round((float)$state * (float)($get('unit_price') ?? 0), 2));
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Prix unit. HT')
                                    ->numeric()
                                    ->required()
                                    ->suffix('€')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $set('total', round((float)($get('quantity') ?? 1) * (float)$state, 2));
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('total')
                                    ->label('Total HT')
                                    ->numeric()
                                    ->suffix('€')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(2),
                            ])
                            ->addActionLabel('+ Ajouter une ligne')
                            ->columnSpanFull(),

                        // Taux TVA + Totaux
                        Forms\Components\Grid::make(2)->schema([

                            Forms\Components\Section::make('TVA')
                                ->schema([
                                    Forms\Components\TextInput::make('tax_rate')
                                        ->label('Taux de TVA (%)')
                                        ->numeric()
                                        ->default(2.10)
                                        ->suffix('%')
                                        ->required(),
                                ])
                                ->columnSpan(1),

                            Forms\Components\Section::make('Totaux calculés')
                                ->schema([
                                    Forms\Components\Placeholder::make('subtotal_display')
                                        ->label('Sous-total HT')
                                        ->content(function(Get $get) {
                                            $lines = $get('lines') ?? [];
                                            $total = array_sum(array_column($lines, 'total'));
                                            return number_format((float)$total, 2, ',', ' ') . ' €';
                                        }),
                                    Forms\Components\Placeholder::make('tax_display')
                                        ->label(fn(Get $get) => 'TVA (' . number_format((float)($get('tax_rate') ?? 2.10), 2, ',', ' ') . ' %)')
                                        ->content(function(Get $get) {
                                            $lines    = $get('lines') ?? [];
                                            $subtotal = array_sum(array_column($lines, 'total'));
                                            $tva      = round($subtotal * (float)($get('tax_rate') ?? 2.10) / 100, 2);
                                            return number_format($tva, 2, ',', ' ') . ' €';
                                        }),
                                    Forms\Components\Placeholder::make('total_display')
                                        ->label('Total TTC')
                                        ->content(function(Get $get) {
                                            $lines    = $get('lines') ?? [];
                                            $subtotal = array_sum(array_column($lines, 'total'));
                                            $tva      = round($subtotal * (float)($get('tax_rate') ?? 2.10) / 100, 2);
                                            return new \Illuminate\Support\HtmlString(
                                                '<span class="font-bold text-xl text-primary-600">'
                                                . number_format($subtotal + $tva, 2, ',', ' ') . ' €</span>'
                                            );
                                        }),
                                ])
                                ->columns(3)
                                ->columnSpan(1),
                        ]),
                    ]),

                // ══ Onglet Paiement ═══════════════════════════════════════════
                Forms\Components\Tabs\Tab::make('Paiement')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->label('Mode de règlement')
                            ->options(collect(PaymentMethod::cases())
                                ->mapWithKeys(fn($p) => [$p->value => $p->label()]))
                            ->nullable(),

                        Forms\Components\TextInput::make('payment_reference')
                            ->label('Référence de paiement')
                            ->nullable()
                            ->placeholder('N° chèque, virement…'),

                        Forms\Components\DatePicker::make('paid_at')
                            ->label('Date de paiement')
                            ->nullable(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

            ])->columnSpanFull(),
        ]);
    }

    // ── Table ──────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('N° Facture')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('client.display_name')
                    ->label('Bénéficiaire')
                    ->searchable(query: fn($query, string $search) => $query->whereHas('client', fn($q) => $q
                        ->where('last_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")))
                    ->sortable(),

                Tables\Columns\TextColumn::make('payer.display_name')
                    ->label('Payeur')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn(Invoice $r) => $r->isOverdue() || (
                        ! $r->isPaid() && $r->due_date?->isPast()
                    ) ? 'danger' : null),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total TTC')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn($state) => (is_string($state)
                        ? InvoiceStatus::from($state)
                        : $state)->color())
                    ->formatStateUsing(fn($state) => (is_string($state)
                        ? InvoiceStatus::from($state)
                        : $state)->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Payée le')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('invoice_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(InvoiceStatus::cases())
                        ->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                    ->multiple(),

                Tables\Filters\Filter::make('impayees')
                    ->label('Impayées')
                    ->query(fn($query) => $query->whereNotIn('status', ['paid', 'cancelled'])),

                Tables\Filters\Filter::make('en_retard')
                    ->label('En retard')
                    ->query(fn($query) => $query
                        ->where('status', '!=', 'paid')
                        ->where('due_date', '<', now())),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // ── Télécharger PDF ──────────────────────────────────────────
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (Invoice $record) {
                        $record->load('lines', 'client', 'payer');
                        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $record])
                            ->setPaper('a4', 'portrait');
                        return response()->streamDownload(
                            fn() => print($pdf->output()),
                            $record->invoice_number . '.pdf'
                        );
                    }),

                // ── Marquer envoyée ──────────────────────────────────────────
                Tables\Actions\Action::make('marquer_envoyee')
                    ->label('Envoyer')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn(Invoice $r) => $r->isDraft())
                    ->requiresConfirmation()
                    ->modalHeading('Marquer comme envoyée ?')
                    ->action(function (Invoice $record) {
                        $record->update(['status' => InvoiceStatus::Sent]);
                        Notification::make()->title('Facture marquée comme envoyée')->success()->send();
                    }),

                // ── Marquer payée ────────────────────────────────────────────
                Tables\Actions\Action::make('marquer_payee')
                    ->label('Paiement reçu')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Invoice $r) => $r->isSent() || $r->isOverdue())
                    ->form([
                        Forms\Components\DatePicker::make('paid_at')
                            ->label('Date de paiement')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('payment_method')
                            ->label('Mode de règlement')
                            ->options(collect(PaymentMethod::cases())
                                ->mapWithKeys(fn($p) => [$p->value => $p->label()]))
                            ->required(),
                        Forms\Components\TextInput::make('payment_reference')
                            ->label('Référence')
                            ->placeholder('N° chèque, virement…'),
                    ])
                    ->action(function (Invoice $record, array $data) {
                        $record->update([
                            'status'             => InvoiceStatus::Paid,
                            'paid_at'            => $data['paid_at'],
                            'payment_method'     => $data['payment_method'],
                            'payment_reference'  => $data['payment_reference'] ?? null,
                        ]);
                        Notification::make()->title('Facture marquée comme payée')->success()->send();
                    }),

                // ── Marquer en retard ────────────────────────────────────────
                Tables\Actions\Action::make('marquer_retard')
                    ->label('En retard')
                    ->icon('heroicon-o-exclamation-circle')
                    ->color('danger')
                    ->visible(fn(Invoice $r) => $r->isSent())
                    ->requiresConfirmation()
                    ->action(function (Invoice $record) {
                        $record->update(['status' => InvoiceStatus::Overdue]);
                        Notification::make()->title('Facture marquée en retard')->warning()->send();
                    }),

                // ── Annuler ──────────────────────────────────────────────────
                Tables\Actions\Action::make('annuler')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Invoice $r) => $r->isDraft() || $r->isSent())
                    ->requiresConfirmation()
                    ->modalHeading('Annuler la facture ?')
                    ->modalDescription('Cette action est irréversible.')
                    ->action(function (Invoice $record) {
                        $record->update(['status' => InvoiceStatus::Cancelled]);
                        Notification::make()->title('Facture annulée')->warning()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_pdf_selection')
                        ->label('Télécharger PDF (sélection)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            // Export multi-pages — une facture par page
                            $html = $records->map(function ($invoice) {
                                $invoice->load('lines', 'client', 'payer');
                                return view('pdf.invoice', ['invoice' => $invoice])->render();
                            })->implode('<div style="page-break-after:always;"></div>');

                            $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
                            return response()->streamDownload(
                                fn() => print($pdf->output()),
                                'factures-selection-' . now()->format('Ymd') . '.pdf'
                            );
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->striped();
    }

    // ── Pages ──────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit'   => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
