<?php

namespace App\Filament\Resources\CustomFeederResource\Pages;

use App\Filament\Resources\CustomFeederResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomFeeder extends CreateRecord
{
    protected static string $resource = CustomFeederResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
