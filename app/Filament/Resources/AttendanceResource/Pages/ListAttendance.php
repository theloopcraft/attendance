<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Actions\Attendance\SyncAttendance;
use App\Actions\Attendance\SyncAttendanceToServer;
use App\Filament\Resources\AttendanceResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;

class ListAttendance extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getActions(): array
    {
        return [
//            ActionGroup::make([

            Action::make('Server')
                ->label('Upload')
                ->icon('heroicon-s-arrow-up-tray')
                ->action(fn() => SyncAttendanceToServer::run()),

            Action::make('Devices')
                ->label('Download')
                ->icon('heroicon-s-arrow-down-tray')
                ->action(fn() => SyncAttendance::run()),
//            ]),

        ];
    }
}
