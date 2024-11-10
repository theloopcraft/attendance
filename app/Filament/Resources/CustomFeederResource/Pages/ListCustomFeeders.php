<?php

namespace App\Filament\Resources\CustomFeederResource\Pages;

use App\Filament\Resources\CustomFeederResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomFeeders extends ListRecords
{
    protected static string $resource = CustomFeederResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
