<?php

namespace App\Filament\Resources\DeviceResource\Pages;

use App\Actions\User\SyncUserFromDevice;
use App\Filament\Resources\DeviceResource;
use App\Models\Device;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDevice extends EditRecord
{
    protected static string $resource = DeviceResource::class;

    protected function getActions(): array
    {
        return [
//            ActionGroup::make([

                Action::make('Test')
                    ->icon('heroicon-s-bell-alert')
                    ->label('Test Connection')
//                    ->disabled(fn (Device $record) => $record->type == 'anviz')
                    ->action(function (Device $record) {
                        Device::testVoice($record);
                    }),

                Action::make('Users')
//                    ->disabled(fn (Device $record) => $record->type == 'anviz')
//                    ->hidden(fn (Device $record) => ! $record->is_active)
                    ->icon('heroicon-s-users')
                    ->action(function () {
                        SyncUserFromDevice::dispatchSync();
                    }),

                Action::make('Reboot')
//                    ->disabled(fn (Device $record) => $record->type == 'anviz')
//                    ->hidden(fn (Device $record) => ! $record->is_active)
                    ->icon('heroicon-s-arrow-path')
                    ->requiresConfirmation()
                    ->action(function (Device $record) {
                        Device::reboot($record);
                    }),

                Action::make('Attendance')
//                    ->disabled(fn (Device $record) => $record->type == 'anviz')
//                    ->hidden(fn (Device $record) => ! $record->is_active)
                    ->icon('heroicon-s-backspace')
                    ->requiresConfirmation()
                    ->color('warning')
                    ->modalHeading('Clearing attendance logs')
                    ->modalDescription('Are you sure you\'d like to delete this post? This cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete it')
                    ->label('Clear Logs')
                    ->action(function (Device $record) {
                        Device::clearLogs($record);
                    }),

                DeleteAction::make(),
//            ]),
        ];
    }
}
