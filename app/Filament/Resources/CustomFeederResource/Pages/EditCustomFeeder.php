<?php

namespace App\Filament\Resources\CustomFeederResource\Pages;

use App\Filament\Resources\CustomFeederResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomFeeder extends EditRecord
{
    protected static string $resource = CustomFeederResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
