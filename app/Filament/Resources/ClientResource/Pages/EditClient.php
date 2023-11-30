<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\HumanlotClient;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('Test Connection')
                    ->color('success')
                    ->icon('heroicon-o-bolt')
                    ->action(function (HumanlotClient $record) {
                        $response = $record->validateToken();

                        if (! $response->ok()) {
                            $record->update(['status' => 0]);
                            Notification::make()
                                ->title('It appears an invalid token has been provided, Please double-check.')
                                ->danger()
                                ->send();

                            return;
                        }
                        $record->update(['status' => 1]);
                        Notification::make()
                            ->title('The token supplied is valid.')
                            ->success()
                            ->send();

                    }),

                DeleteAction::make(),
            ]),

        ];
    }

    protected function afterSave(): void
    {
        $response = $this->record->validateToken();

        if (! $response->ok()) {
            $this->record->update(['status' => 0]);

            return;
        }
        $this->record->update(['status' => 1]);
    }
}
