<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Actions\Attendance\SyncAttendance;
use App\Actions\Attendance\SyncAttendanceToServer;
use App\Filament\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ListAttendance extends ListRecords
{
    protected static string $resource = AttendanceResource::class;


    protected function getActions(): array
    {
        return [
            ActionGroup::make([

                Action::make('Server')
                    ->label('Upload')
                    ->icon('heroicon-s-arrow-up-tray')
                    ->action(fn() => SyncAttendanceToServer::run()),

                Action::make('Devices')
                    ->label('Download')
                    ->icon('heroicon-s-arrow-down-tray')
                    ->action(fn() => SyncAttendance::run()),
            ])

        ];
    }
}
