<?php
namespace App\Filament\Resources;
use App\Filament\Resources\MagazineIssueResource\Pages;
use App\Models\MagazineIssue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class MagazineIssueResource extends Resource
{
    protected static ?string $model = MagazineIssue::class;
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup = 'Contenu';
    protected static ?string $navigationLabel = 'Numeros';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations')->schema([
                Forms\Components\Select::make('magazine_id')
                    ->label('Publication')->relationship('magazine', 'name')
                    ->required()->searchable(),
                Forms\Components\TextInput::make('issue_number')->label('Numero')->required(),
                Forms\Components\TextInput::make('title')->label('Titre')->required(),
                Forms\Components\TextInput::make('month_label')->label('Mois de parution'),
                Forms\Components\DatePicker::make('publication_date')->label('Date publication')->required(),
                Forms\Components\Textarea::make('description')->rows(2),
            ])->columns(2),
            Forms\Components\Section::make('PDF')->schema([
                Forms\Components\FileUpload::make('pdf_file')
                    ->label('PDF')->disk('local')->directory('publications/pdf')
                    ->acceptedFileTypes(['application/pdf'])->maxSize(102400),
                Forms\Components\Toggle::make('is_published')->label('Publie')->default(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_path')
                    ->label('')->disk('public')->width(60)->height(80),
                Tables\Columns\TextColumn::make('magazine.name')
                    ->label('Publication')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('issue_number')->label('N')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Titre')->limit(40),
                Tables\Columns\TextColumn::make('month_label')->label('Mois'),
                Tables\Columns\TextColumn::make('publication_date')
                    ->label('Date')->date('d/m/Y')->sortable(),
                Tables\Columns\IconColumn::make('is_published')->label('Publie')->boolean(),
            ])
            ->defaultSort('publication_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('magazine_id')
                    ->label('Publication')->relationship('magazine', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Liseuse')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (MagazineIssue $record) => route('admin.preview.reader', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (MagazineIssue $record) => (bool) $record->pdf_file),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('routing')
                    ->label('Routage')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (MagazineIssue $record) {
                        $r = (new \App\Services\PostalRoutingService())->generateRoutingFile($record);
                        \Filament\Notifications\Notification::make()
                            ->title("Routage: {$r->total_recipients} dest.")
                            ->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMagazineIssues::route('/'),
            'create' => Pages\CreateMagazineIssue::route('/create'),
            'edit'   => Pages\EditMagazineIssue::route('/{record}/edit'),
        ];
    }
}
