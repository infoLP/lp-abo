<?php
namespace App\Filament\Resources;
use App\Filament\Resources\PaymentResource\Pages; use App\Models\Client; use App\Models\Payment;
use Filament\Forms; use Filament\Forms\Form; use Filament\Resources\Resource; use Filament\Tables; use Filament\Tables\Table;
class PaymentResource extends Resource {
    protected static ?string $model = Payment::class; protected static ?string $navigationIcon = 'heroicon-o-banknotes'; protected static ?string $navigationGroup = 'Comptabilité'; protected static ?string $navigationLabel = 'Reglements'; protected static ?int $navigationSort = 2;
    public static function form(Form $form): Form {
        $clientOptions = Client::where('status','active')->get()->mapWithKeys(fn(Client $c)=>[$c->id=>"{$c->client_number} - {$c->full_name}"])->toArray();
        return $form->schema([Forms\Components\Section::make()->schema([
            Forms\Components\Select::make('client_id')->label('Client')->options($clientOptions)->searchable()->required(),
            Forms\Components\Select::make('invoice_id')->label('Facture')->relationship('invoice','invoice_number')->searchable(),
            Forms\Components\TextInput::make('amount')->label('Montant')->numeric()->prefix('EUR')->required(),
            Forms\Components\Select::make('method')->label('Mode')->options(['card'=>'CB','sepa'=>'SEPA','check'=>'Cheque','transfer'=>'Virement','cash'=>'Especes','free'=>'Gratuit'])->required(),
            Forms\Components\Select::make('status')->label('Statut')->options(['pending'=>'En attente','completed'=>'Encaisse','failed'=>'Echoue','refunded'=>'Rembourse'])->default('completed'),
            Forms\Components\DatePicker::make('payment_date')->label('Date')->required()->default(now()),
            Forms\Components\TextInput::make('reference')->label('Reference'),
            Forms\Components\Textarea::make('notes')->rows(2)->columnSpanFull(),
        ])->columns(2)]);
    }
    public static function table(Table $table): Table { return $table->columns([
        Tables\Columns\TextColumn::make('payment_number')->label('N')->searchable()->sortable(),
        Tables\Columns\TextColumn::make('client.full_name')->label('Client'),
        Tables\Columns\TextColumn::make('amount')->label('Montant')->money('EUR')->sortable(),
        Tables\Columns\TextColumn::make('method')->label('Mode')->badge(),
        Tables\Columns\TextColumn::make('status')->label('Statut')->badge()->color(fn(string $state)=>match($state){'completed'=>'success','pending'=>'warning','failed'=>'danger',default=>'gray'}),
        Tables\Columns\TextColumn::make('payment_date')->label('Date')->date('d/m/Y')->sortable(),
    ])->defaultSort('payment_date','desc')->actions([Tables\Actions\EditAction::make()]); }
    public static function getPages(): array { return ['index'=>Pages\ListPayments::route('/'),'create'=>Pages\CreatePayment::route('/create'),'edit'=>Pages\EditPayment::route('/{record}/edit')]; }
}
