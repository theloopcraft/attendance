<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Pending', Attendance::query()->whereNull('sync_at')->count())
                ->description('Pending Attendance')
                ->descriptionIcon('heroicon-o-clock')
                ->color('danger'),

            Stat::make('Total Attendance', number_format(Attendance::count()))
                ->description('Total Attendance')
                ->descriptionIcon('heroicon-o-bolt')
                ->color('success'),

            Stat::make('Users', number_format(User::count()))
                ->description('Total Users')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success'),
        ];
    }
}
