<?php

namespace Flexpik\FilamentStudio\Resources\CollectionManagerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Flexpik\FilamentStudio\Models\StudioField;

class MigrationLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'migrationLogs';

    protected static ?string $title = 'Audit Log';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-clipboard-document-list';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('operation')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'create_collection' => 'success',
                        'update_collection' => 'info',
                        'delete_collection' => 'danger',
                        'add_field' => 'success',
                        'update_field' => 'info',
                        'rename_field' => 'warning',
                        'delete_field' => 'danger',
                        'reorder_fields' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('field_id')
                    ->label('Field')
                    ->formatStateUsing(function (?int $state) {
                        if (! $state) {
                            return '—';
                        }

                        $field = StudioField::find($state);

                        return $field !== null ? $field->label : "#{$state} (deleted)";
                    }),
                Tables\Columns\TextColumn::make('performer.name')
                    ->label('Performed By')
                    ->default('System'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
