<?php
namespace App\Filament\Resources\ClientResource\Widgets;
use App\Models\Client; use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget; use Filament\Widgets\StatsOverviewWidget\Stat;
class ClientStatsWidget extends BaseWidget { protected function getStats(): array { return [
    Stat::make('Actifs', Client::where('status','active')->count())->icon('heroicon-o-check-circle')->color('success'),
    Stat::make('Inactifs', Client::where('status','inactive')->count())->icon('heroicon-o-pause-circle')->color('warning'),
    Stat::make('Archives', Client::where('status','archived')->count())->icon('heroicon-o-archive-box')->color('danger'),
    Stat::make('Expirent 30j', Subscription::where('status','active')->whereNotNull('end_date')->whereBetween('end_date',[now(),now()->addDays(30)])->distinct('client_id')->count('client_id'))->icon('heroicon-o-clock')->color('info'),
]; } }
