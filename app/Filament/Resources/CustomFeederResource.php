<?php

namespace App\Filament\Resources;

use App\Actions\Custom\ConvertLog;
use App\Actions\Custom\GetAttendanceLogsFromCustomFeeder;
use App\Filament\Resources\CustomFeederResource\Pages;
use App\Models\FeederLog;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class CustomFeederResource extends Resource
{
    protected static ?string $model = FeederLog::class;

    protected static ?string $slug = 'custom-feeders';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('unique_id'),

                TextInput::make('staff_no'),

                TextInput::make('status')
                    ->required(),

                TextInput::make('name'),

                TextInput::make('action'),

                TextInput::make('action_at'),

                TextInput::make('action_code'),

                TextInput::make('device'),

                Placeholder::make('created_at')
                    ->label('Created Date')
                    ->content(fn(?FeederLog $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                Placeholder::make('updated_at')
                    ->label('Last Modified Date')
                    ->content(fn(?FeederLog $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('action_at', 'desc')
            ->columns([
                TextColumn::make('unique_id'),

                TextColumn::make('staff_no'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->colors([
                        'pending' => 'warning',
                        'completed' => 'success',
                    ])
                    ->badge(),

                TextColumn::make('device'),

                TextColumn::make('action_code'),

                TextColumn::make('action'),

                TextColumn::make('action_at')
                    ->sortable(),
            ])
            ->headerActions([

                Action::make('convert')
                    ->icon('heroicon-o-arrow-left-end-on-rectangle')
                    ->color('success')
                    ->action(function () {
                        $feederLogs = FeederLog::query()
                            ->where('status', 'pending')
                            ->lazyById();

                        if ($feederLogs->count() == 0) {
                            return Notification::make()
                                ->title('No records found to convert.')
                                ->danger()
                                ->send();
                        }

                        foreach ($feederLogs as $log) {
                            ConvertLog::run($log);
                        }

                        return Notification::make()
                            ->title('Records have been successfully converted to the attendance.')
                            ->success()
                            ->send();

                    }),

                Action::make('fetch')
                    ->icon('heroicon-o-rss')
                    ->color('warning')
                    ->modalWidth('sm')
                    ->form([
                        Section::make('Fetch attendance feeder')
                            ->schema([
                                DatePicker::make('start_date')
                                    ->native(false)
                                    ->default(now()),
                            ]),
                    ])
                    ->action(function (array $data) {
                        ini_set('max_execution_time', 300);
                        ini_set('memory_limit', '2048M');

                        $startDate = Carbon::parse($data['start_date'])->startOfDay();
                        $endDate = Carbon::parse($data['start_date'])->addDay()->endOfDay();

                        GetAttendanceLogsFromCustomFeeder::run($startDate, $endDate);
                        return Notification::make()
                            ->title('Records have been successfully fetched.')
                            ->success()
                            ->send();
                    })
            ])
            ->filters([

                SelectFilter::make('status')
                    ->options(['pending' => 'Pending',]),

                Filter::make('action_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->native(false),
                        DatePicker::make('created_until')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('action_at', '>=',
                                    Carbon::parse($date)->toDateTimeString()),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('action_at', '<=',
                                    Carbon::parse($date)->toDateTimeString()),
                            );
                    })
            ])
            ->actions([
//                EditAction::make(),
//                DeleteAction::make(),
            ])
            ->bulkActions([
//                BulkActionGroup::make([
//                    DeleteBulkAction::make(),
//                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomFeeders::route('/'),
            'create' => Pages\CreateCustomFeeder::route('/create'),
//            'edit' => Pages\EditCustomFeeder::route('/{record}/edit'),
        ];
    }

}
