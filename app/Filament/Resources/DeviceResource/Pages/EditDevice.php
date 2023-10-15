<?php

namespace App\Filament\Resources\DeviceResource\Pages;

use App\Actions\User\SyncUserFromDevice;
use App\Filament\Resources\DeviceResource;
use App\Models\Device;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDevice extends EditRecord
{
    protected static string $resource = DeviceResource::class;

    protected function getActions(): array
    {
        return [
            ActionGroup::make([

                Action::make('Test')
                    ->icon('heroicon-s-bell-alert')
                    ->label('Test Connection')
                    ->disabled($this->record->type == 'anviz')
                    ->action(function () {
//                        $ip = $this->record->ip;
//                        exec("ping -c 4 $ip", $output, $result);
//
//                        if ($result == 0) {
                            Device::testVoice($this->record);
//                        } else {
//                            $this->record->update(['is_active' => 0]);
//                            Notification::make()
//                                ->title('The provided IP address is not valid.')
//                                ->danger()
//                                ->send();
//                        }
                    }),

                Action::make('Users')
                    ->hidden(!$this->record->is_active)
                    ->icon('heroicon-s-users')
                    ->disabled($this->record->type == 'anviz')
                    ->action(function () {
                        SyncUserFromDevice::dispatch();
                    }),

//                Action::make('Reboot')
//                    ->hidden(!$this->record->is_active)
//                    ->icon('heroicon-s-arrow-path')
//                    ->requiresConfirmation()
//                    ->disabled($this->record->type == 'anviz')
//                    ->action(function () {
//                        Device::reboot($this->record);
//                    }),

                Action::make('Attendance')
                    ->hidden(!$this->record->is_active)
                    ->icon('heroicon-s-backspace')
                    ->requiresConfirmation()
                    ->color('warning')
                    ->modalHeading('Clearing attendance logs')
                    ->modalDescription('Are you sure you\'d like to delete this post? This cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete it')
                    ->label('Clear Logs')
                    ->disabled($this->record->type == 'anviz')
                    ->action(function () {
//                        Device::clearLogs($this->record);
                    }),

                DeleteAction::make(),
            ]),
        ];
    }
}
