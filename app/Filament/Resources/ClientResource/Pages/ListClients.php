<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected static ?string $title = 'Clients';

    protected function getActions(): array
    {
        return [
            ActionGroup::make([
                CreateAction::make()->color('success')->label('Create'),
            ]),
        ];
    }
}
