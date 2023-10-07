<?php

namespace App\Filament\Resources\DeviceResource\Pages;

use App\Filament\Resources\DeviceResource;
use App\Models\Device;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\EditRecord;

class EditDevice extends EditRecord
{
    protected static string $resource = DeviceResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make('Test')
                ->label('Voice')
                ->disabled($this->record->type == 'anviz')
//               ->icon('heroicon-o-volume-up')
                ->action(function () {
                    Device::testVoice($this->record);
                })
                ->color('primary'),
            //           $this->getDeleteAction(),
        ];
    }
}
