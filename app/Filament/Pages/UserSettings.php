<?php

namespace App\Filament\Pages;

use App\Models\UserPreference;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class UserSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationLabel = 'Mes préférences';
    protected static ?string $navigationGroup = 'Paramètres';
    protected static ?string $slug            = 'mes-preferences';
    protected static ?string $title           = 'Mes préférences d\'affichage';
    protected static ?int    $navigationSort  = 10;
    protected static string  $view            = 'filament.pages.user-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'clients_sort_column'    => UserPreference::get('clients_sort_column', 'created_at'),
            'clients_sort_direction' => UserPreference::get('clients_sort_direction', 'desc'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('clients_sort_column')
                    ->label('Colonne de tri par défaut — Liste clients')
                    ->options([
                        'created_at'   => 'Date de création',
                        'last_name'    => 'Nom du contact',
                        'company_name' => 'Raison sociale',
                        'city'         => 'Ville',
                        'postal_code'  => 'Code postal',
                        'client_number'=> 'N° Client',
                        'status'       => 'Statut',
                    ])
                    ->default('created_at')
                    ->required(),

                Select::make('clients_sort_direction')
                    ->label('Ordre de tri')
                    ->options([
                        'asc'  => 'Croissant (A → Z)',
                        'desc' => 'Décroissant (Z → A)',
                    ])
                    ->default('desc')
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        UserPreference::set('clients_sort_column',    $data['clients_sort_column']);
        UserPreference::set('clients_sort_direction', $data['clients_sort_direction']);

        Notification::make()
            ->title('Préférences enregistrées')
            ->success()
            ->send();
    }
}
