<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Actions\Attendance\SyncAttendance;
use App\Actions\Attendance\SyncAttendanceToServer;
use App\Filament\Resources\AttendanceResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

class ListAttendance extends ListRecords
{
    protected static string $resource = AttendanceResource::class;


    protected function getActions(): array
    {
        return [
            ActionGroup::make([

                Action::make('Devices')
                    ->label('Download')
                    ->color('warning')
                    ->icon('heroicon-s-arrow-down-tray')
                    ->action(fn() => SyncAttendance::run()),

                Action::make('Server')
                    ->label('Upload')
                    ->color('success')
                    ->icon('heroicon-s-arrow-path-rounded-square')
                    ->action(fn() => SyncAttendanceToServer::run()),
            ])


        ];
    }
}
