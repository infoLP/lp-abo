<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Mail\AdminPasswordResetMail;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model            = User::class;
    protected static ?string $navigationIcon   = 'heroicon-o-users';
    protected static ?string $navigationGroup  = 'Paramètres';
    protected static ?string $navigationLabel  = 'Utilisateurs';
    protected static ?string $modelLabel       = 'Utilisateur';
    protected static ?string $pluralModelLabel = 'Utilisateurs';
    protected static ?int    $navigationSort   = 10;

    // Seuls admin et director peuvent gérer les utilisateurs
    public static function canAccess(): bool
    {
        return Auth::user()?->hasAnyRole(['admin', 'director']) ?? false;
    }

    // ── Labels des rôles ──────────────────────────────────────────────────
    private static function roleLabels(): array
    {
        return [
            'admin'     => 'Administrateur',
            'director'  => 'Directeur',
            'manager'   => 'Responsable abonnements',
            'accountant'=> 'Comptable',
            'client'    => 'Client',
        ];
    }

    private static function roleColors(): array
    {
        return [
            'admin'     => 'danger',
            'director'  => 'warning',
            'manager'   => 'info',
            'accountant'=> 'success',
            'client'    => 'gray',
        ];
    }

    // ── Formulaire ────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        $isEdit = $form->getRecord() !== null;

        return $form->schema([

            Forms\Components\Section::make('Identité')
                ->schema([
                    Forms\Components\TextInput::make('first_name')
                        ->label('Prénom')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\TextInput::make('last_name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('Téléphone')
                        ->tel()
                        ->nullable(),
                ])->columns(2),

            Forms\Components\Section::make('Accès & Rôle')
                ->schema([
                    Forms\Components\Select::make('roles')
                        ->label('Rôle')
                        ->options(
                            Role::whereIn('name', array_keys(self::roleLabels()))
                                ->get()
                                ->mapWithKeys(fn($r) => [
                                    $r->name => self::roleLabels()[$r->name] ?? $r->name,
                                ])
                        )
                        ->required()
                        ->native(false)
                        ->helperText('Un utilisateur ne peut avoir qu\'un seul rôle'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Compte actif')
                        ->default(true)
                        ->helperText('Un compte inactif ne peut pas se connecter'),
                ])->columns(2),

            Forms\Components\Section::make($isEdit ? 'Modifier le mot de passe' : 'Mot de passe')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label($isEdit ? 'Nouveau mot de passe' : 'Mot de passe')
                        ->password()
                        ->revealable()
                        ->required(! $isEdit)
                        ->nullable($isEdit)
                        ->minLength(8)
                        ->dehydrated(fn($state) => filled($state))
                        ->dehydrateStateUsing(fn($state) => Hash::make($state))
                        ->helperText($isEdit ? 'Laisser vide pour ne pas modifier' : 'Minimum 8 caractères'),
                    Forms\Components\TextInput::make('password_confirmation')
                        ->label('Confirmer le mot de passe')
                        ->password()
                        ->revealable()
                        ->required(! $isEdit)
                        ->nullable($isEdit)
                        ->same('password')
                        ->dehydrated(false),
                ])->columns(2),

        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width('50px'),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nom')
                    ->getStateUsing(fn(User $r) => trim($r->first_name . ' ' . $r->last_name) ?: $r->name)
                    ->searchable(query: fn($query, string $search) => $query
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name',  'like', "%{$search}%")
                        ->orWhere('name',       'like', "%{$search}%"))
                    ->sortable(query: fn($query, string $dir) => $query->orderBy('last_name', $dir))
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rôle')
                    ->badge()
                    ->formatStateUsing(fn($state) => self::roleLabels()[$state] ?? $state)
                    ->color(fn($state) => self::roleColors()[$state] ?? 'gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('client.display_name')
                    ->label('Client lié')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Dernière connexion')
                    ->placeholder('Jamais')
                    ->date('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_name')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rôle')
                    ->options(self::roleLabels())
                    ->query(fn($query, array $data) => $data['value']
                        ? $query->whereHas('roles', fn($q) => $q->where('name', $data['value']))
                        : $query),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs'),

                Tables\Filters\TernaryFilter::make('has_client')
                    ->label('Client lié')
                    ->placeholder('Tous')
                    ->trueLabel('Avec client')
                    ->falseLabel('Sans client')
                    ->queries(
                        true:  fn($q) => $q->whereNotNull('client_id'),
                        false: fn($q) => $q->whereNull('client_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // ── Activer / Désactiver ──────────────────────────────────
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn(User $r) => $r->is_active ? 'Désactiver' : 'Activer')
                    ->icon(fn(User $r) => $r->is_active
                        ? 'heroicon-o-lock-closed'
                        : 'heroicon-o-lock-open')
                    ->color(fn(User $r) => $r->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn(User $r) => $r->is_active
                        ? 'Désactiver le compte ?'
                        : 'Activer le compte ?')
                    ->visible(fn(User $r) => $r->id !== Auth::id()) // pas sur soi-même
                    ->action(function (User $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()
                            ->title($record->is_active ? 'Compte activé' : 'Compte désactivé')
                            ->success()
                            ->send();
                    }),

                // ── Envoyer lien réinitialisation MDP par email ──────
                Tables\Actions\Action::make('reset_password')
                    ->label('Envoyer lien MDP')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer un lien de réinitialisation ?')
                    ->modalDescription(fn(User $r) => "Un email sera envoyé à {$r->email} avec un lien valable 60 minutes.")
                    ->modalSubmitActionLabel('Envoyer')
                    ->action(function (User $record) {
                        try {
                            $token    = Password::broker()->createToken($record);
                            $resetUrl = url(route('password.set', [
                                'token' => $token,
                                'email' => $record->email,
                            ]));

                            Mail::to($record->email)
                                ->send(new AdminPasswordResetMail($record, $resetUrl));

                            Notification::make()
                                ->title('Email envoyé')
                                ->body("Lien de réinitialisation envoyé à {$record->email}")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Erreur d\'envoi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // ── Supprimer ─────────────────────────────────────────────
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(User $r) => $r->id !== Auth::id())
                    ->before(function (User $record, Tables\Actions\DeleteAction $action) {
                        if ($record->hasRole('admin') &&
                            User::role('admin')->count() <= 1) {
                            Notification::make()
                                ->title('Suppression impossible')
                                ->body('Il doit rester au moins un administrateur.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activer la sélection')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Désactiver la sélection')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records
                                ->filter(fn($r) => $r->id !== Auth::id())
                                ->each->update(['is_active' => false]);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->striped();
    }

    // ── Pages ─────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
