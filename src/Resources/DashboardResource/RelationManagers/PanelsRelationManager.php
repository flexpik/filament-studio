<?php

namespace Flexpik\FilamentStudio\Resources\DashboardResource\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Flexpik\FilamentStudio\Models\StudioPanel;

class PanelsRelationManager extends RelationManager
{
    protected static string $relationship = 'panels';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('panel_type')->label('Type')->badge(),
                TextColumn::make('header_label')->label('Label'),
                TextColumn::make('grid_col_span')->label('Columns'),
                TextColumn::make('grid_order')->label('Order')->sortable(),
            ])
            ->defaultSort('grid_order')
            ->actions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, StudioPanel $record): array {
                        $data['config'] = $record->merged_config;

                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->reorderable('grid_order');
    }
}
