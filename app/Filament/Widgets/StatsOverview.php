<?php
namespace App\Filament\Widgets;
use App\Models\Client; use App\Models\Contact; use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
class StatsOverview extends BaseWidget
{
    protected function getStats(): array {
        return [
            Stat::make('Abonnements actifs', Subscription::where('status','active')->count())->color('success'),
            Stat::make('Clients actifs', Client::where('status','active')->count())->color('info'),
            Stat::make('Messages', Contact::where('status','new')->count())->color('danger'),
            Stat::make('Expirent 30j', Subscription::where('status','active')->whereNotNull('end_date')->whereBetween('end_date',[now(),now()->addDays(30)])->count())->color('warning'),
        ];
    }
}
