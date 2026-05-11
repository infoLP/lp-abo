<?php
namespace App\Filament\Resources;
use App\Filament\Resources\CustomFieldDefinitionResource\Pages; use App\Models\CustomFieldDefinition;
use Filament\Forms; use Filament\Forms\Form; use Filament\Resources\Resource; use Filament\Tables; use Filament\Tables\Table; use Illuminate\Support\Str;
class CustomFieldDefinitionResource extends Resource {
    protected static ?string $model = CustomFieldDefinition::class; protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal'; protected static ?string $navigationGroup = 'Paramètres'; protected static ?string $navigationLabel = 'Champs personnalises'; protected static ?int $navigationSort = 10;
    public static function form(Form $form): Form { return $form->schema([Forms\Components\Section::make()->schema([
        Forms\Components\TextInput::make('label')->label('Libelle')->required()->live(onBlur:true)->afterStateUpdated(fn($state,callable $set)=>($set('name',Str::slug($state,'_'))&&$set('slug',Str::slug($state,'_')))),
        Forms\Components\TextInput::make('name')->required(),
        Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord:true),
        Forms\Components\Select::make('type')->label('Type')->options(['text'=>'Texte','textarea'=>'Texte long','number'=>'Nombre','date'=>'Date','select'=>'Liste','boolean'=>'Case a cocher','email'=>'Email','url'=>'URL','phone'=>'Telephone'])->required()->live(),
        Forms\Components\TagsInput::make('options')->label('Valeurs')->visible(fn(callable $get)=>$get('type')==='select'),
        Forms\Components\TextInput::make('group')->label('Groupe')->default('general'),
        Forms\Components\TextInput::make('default_value')->label('Valeur defaut'),
        Forms\Components\Textarea::make('description')->rows(2),
        Forms\Components\Toggle::make('required')->label('Obligatoire'),
        Forms\Components\Toggle::make('is_active')->label('Actif')->default(true),
        Forms\Components\TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
    ])->columns(2)]); }
    public static function table(Table $table): Table { return $table->columns([
        Tables\Columns\TextColumn::make('label')->searchable()->sortable(),
        Tables\Columns\TextColumn::make('type')->badge(),
        Tables\Columns\TextColumn::make('group')->badge()->color('gray'),
        Tables\Columns\IconColumn::make('is_active')->boolean(),
    ])->actions([Tables\Actions\EditAction::make(),Tables\Actions\DeleteAction::make()]); }
    public static function getPages(): array { return ['index'=>Pages\ListCustomFieldDefinitions::route('/'),'create'=>Pages\CreateCustomFieldDefinition::route('/create'),'edit'=>Pages\EditCustomFieldDefinition::route('/{record}/edit')]; }
}
