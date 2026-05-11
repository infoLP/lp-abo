<?php
namespace App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Nouvel utilisateur')];
    }

    public function getTabs(): array
    {
        return [
            'tous' => Tab::make('Tous'),
            'actifs' => Tab::make('Actifs')
                ->modifyQueryUsing(fn(Builder $q) => $q->where('is_active', true))
                ->badge(fn() => \App\Models\User::where('is_active', true)->count()),
            'inactifs' => Tab::make('Inactifs')
                ->modifyQueryUsing(fn(Builder $q) => $q->where('is_active', false))
                ->badge(fn() => \App\Models\User::where('is_active', false)->count())
                ->badgeColor('warning'),
            'admins' => Tab::make('Admins')
                ->modifyQueryUsing(fn(Builder $q) => $q->whereHas('roles',
                    fn($r) => $r->whereIn('name', ['admin', 'director']))),
        ];
    }
}
