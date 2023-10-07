<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\HumanlotClient;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = HumanlotClient::class;

    protected static ?string $label = 'Clients';

    protected static ?string $navigationLabel = 'Clients';

    protected static ?string $slug = 'clients';

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make()
                ->inlineLabel()
                ->schema([
                    TextInput::make('app_id')
                        ->label('APP ID')
                        ->required(),

                    TextInput::make('secret')
                        ->label('TOKEN')
                        ->required(),

                    Select::make('base_url')
                        ->label('Environment')
                        ->searchable()
                        ->options([
                            'https://sandbox-apps.humanlot.com/api' => 'Live',
                            'https://foshigandu.humanlot.com/api' => 'Dev'
                        ])
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('app_id')->label('APP ID'),
                TextColumn::make('secret')->label('Token'),
                TextColumn::make('base_url')->color('warning')->toggledHiddenByDefault(),
                TextColumn::make('status')->label('Active')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        '1' => 'success',
                        '0' => 'danger',
                        default => 'gray'
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

}
