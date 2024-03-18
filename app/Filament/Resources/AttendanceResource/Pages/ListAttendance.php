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

}
