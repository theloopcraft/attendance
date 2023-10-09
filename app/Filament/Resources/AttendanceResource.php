<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $pluralLabel = 'Attendance';


    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->paginated([15, 50, 100])
            ->defaultSort('action_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('User')->searchable()->sortable(),

                TextColumn::make('device.name')->label('Device')->sortable(),

                TextColumn::make('action_at')->sortable(),

                TextColumn::make('action')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'default' => 'warning',
                        'Check-in' => 'success',
                        'Check-out' => 'danger',
                    }),

                TextColumn::make('sync_at')
                    ->badge()->label('Synced')
                    ->sortable(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()->label('Export selected'),

                    BulkAction::make('Re-synchronize')
                        ->label('Re-sync selected')
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(fn(Collection $records) => $records->each->update(['sync_at' => null])),

                    DeleteBulkAction::make(),
                ]),

            ])
            ->filters([
                SelectFilter::make('user')->relationship('user', 'name')->searchable(),

                Filter::make('action_at')->form([DatePicker::make('date_from'), DatePicker::make('date_to')])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('action_at', '>=', $date),
                            )
                            ->when($data['date_to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('action_at', '<=', $date),
                            );
                    }),

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendance::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
