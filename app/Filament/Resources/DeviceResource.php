<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceResource\Pages;
use App\Models\Device;
use App\Models\Timezone;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static ?string $navigationIcon = 'heroicon-s-finger-print';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Device Specifications')
                    ->compact()
                    ->schema([
                        Select::make('type')->label('Device Type')
                            ->reactive()
                            ->searchable()
                            ->preload()
                            ->options([
                                'anviz' => 'EP300 Pro – Anviz',
                                'zkt' => 'Zkteco',
                            ])
                            ->default('zkt')
                            ->required(),

                        TextInput::make('name')->label('Device Name')
                            ->required(),

                        Toggle::make('is_active')
                            ->hiddenOn('create')
                            ->label('Status'),

                        TextInput::make('location')->label('Location')
                            ->required(),

                        Select::make('timezone')
                            ->options(Timezone::all()->pluck('name', 'name'))
                            ->searchable(),

                        Select::make('version')
                        ->label('Version')
                        ->native(false)
                        ->options([
                            '1' => 'Version 1',
                            '2' => 'Version 2',
                        ])
                        ->required(),

                        TextInput::make('ip')
                            ->label('Ip address')
                            ->required(),

                        TextInput::make('port')
                            ->label('Port')
                            ->default('4370'),
                    ]),

                Section::make('Device Authentication')
                    ->compact()
                    ->hidden(fn ($get) => $get('type') != 'anviz')
                    ->schema([
                        TextInput::make('device_id'),

                        TextInput::make('user')
                            ->label('Admin')
                            ->required(),

                        TextInput::make('password')
                            ->label('Password'),
                    ]),
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('location')->sortable(),
                TextColumn::make('timezone'),
                TextColumn::make('ip')->searchable(),
                TextColumn::make('port'),
                ToggleColumn::make('is_active')->label('Active')->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDevices::route('/'),
            'create' => Pages\CreateDevice::route('/create'),
            'edit' => Pages\EditDevice::route('/{record}/edit'),
        ];
    }
}
