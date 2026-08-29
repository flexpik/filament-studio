<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Services\RollbackFlowVersion;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Version history';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('version', 'desc')
            ->columns([
                TextColumn::make('version')->label('v')->sortable(),
                TextColumn::make('published_at')->dateTime()->since(),
                TextColumn::make('published_by'),
                TextColumn::make('change_summary')->limit(60),
            ])
            ->actions([
                Action::make('restore')
                    ->requiresConfirmation()
                    ->action(function (StudioFlowVersion $record, RollbackFlowVersion $service) {
                        $service->rollback($this->getOwnerRecord(), $record, (string) auth()->id());
                    }),
            ]);
    }
}
