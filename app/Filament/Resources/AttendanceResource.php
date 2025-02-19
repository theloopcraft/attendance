<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
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
            ->paginated([15, 50, 100])
            ->defaultSort('action_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('User')->searchable()->sortable(),

                TextColumn::make('device.name')->label('Device')->sortable(),

                TextColumn::make('action_at')->sortable(),

                TextColumn::make('action')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            default => 'warning',
                            'Check In', 'Break In' => 'success',
                            'Check Out', 'Break Out' => 'danger',
                        };
                    }),

                IconColumn::make('sync_at')
                    ->label('Synced')
                    ->icons([
                        'heroicon-o-x-circle' => fn($state): bool => $state == 0,
                        'heroicon-s-check-circle' => fn($state): bool => $state != 0,
                    ])
                    ->colors([
                        'danger' => fn($state): bool => $state == 0,
                        'success' => fn($state): bool => $state != 0,
                    ])
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
                        ->action(fn(Collection $records) => $records->each->update(['sync_at' => 0])),

                    DeleteBulkAction::make(),
                ]),

            ])
            ->filters([
                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->preload()
                    ->searchable(),

                Filter::make('action_at')
                    ->form([
                        DatePicker::make('date_from')
                            ->native(false),

                        DatePicker::make('date_to')
                            ->native(false),
                    ])
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
