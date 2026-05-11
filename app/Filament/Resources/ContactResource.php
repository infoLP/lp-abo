<?php
namespace App\Filament\Resources;
use App\Enums\ContactStatus; use App\Filament\Resources\ContactResource\Pages; use App\Models\Contact;
use Filament\Forms; use Filament\Forms\Form; use Filament\Resources\Resource; use Filament\Tables; use Filament\Tables\Table;
class ContactResource extends Resource {
    protected static ?string $model = Contact::class; protected static ?string $navigationIcon = 'heroicon-o-envelope'; protected static ?string $navigationGroup = 'Communication'; protected static ?string $navigationLabel = 'Messages'; protected static ?int $navigationSort = 1;
    public static function getNavigationBadge(): ?string { return (string) static::getModel()::where('status','new')->count() ?: null; }
    public static function form(Form $form): Form { return $form->schema([
        Forms\Components\Section::make('Message')->schema([Forms\Components\TextInput::make('first_name')->label('Prenom')->disabled(),Forms\Components\TextInput::make('last_name')->label('Nom')->disabled(),Forms\Components\TextInput::make('email')->disabled(),Forms\Components\TextInput::make('subject')->label('Sujet')->disabled(),Forms\Components\Textarea::make('message')->disabled()->rows(5)])->columns(2),
        Forms\Components\Section::make('Traitement')->schema([Forms\Components\Select::make('status')->label('Statut')->options(collect(ContactStatus::cases())->mapWithKeys(fn($s)=>[$s->value=>$s->label()]))->required(),Forms\Components\Textarea::make('admin_notes')->label('Notes')->rows(3)]),
    ]); }
    public static function table(Table $table): Table { return $table->columns([
        Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
        Tables\Columns\TextColumn::make('full_name')->label('Nom')->searchable(['first_name','last_name']),
        Tables\Columns\TextColumn::make('email')->searchable(),
        Tables\Columns\TextColumn::make('subject')->label('Sujet')->limit(40),
        Tables\Columns\TextColumn::make('status')->label('Statut')->badge()->formatStateUsing(fn(mixed $state)=>(is_string($state)?\App\Enums\ContactStatus::from($state):$state)->label())->color(fn(mixed $state)=>(is_string($state)?\App\Enums\ContactStatus::from($state):$state)->color()),
    ])->defaultSort('created_at','desc')->actions([Tables\Actions\EditAction::make()->label('Traiter')]); }
    public static function getPages(): array { return ['index'=>Pages\ListContacts::route('/'),'edit'=>Pages\EditContact::route('/{record}/edit')]; }
}
