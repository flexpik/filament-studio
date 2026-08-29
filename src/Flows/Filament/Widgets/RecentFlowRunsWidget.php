<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Illuminate\Database\Eloquent\Builder;

class RecentFlowRunsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Flow Runs';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('flow.name')
                    ->label('Flow')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime()
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->suffix(' ms'),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(function (StudioFlowRun $record): string {
                        return FlowResource::getUrl('view-run', [
                            'record' => $record->flow_id,
                            'runId' => $record->id,
                        ]);
                    }),
            ])
            ->paginated(false);
    }

    protected function getTableQuery(): Builder
    {
        return StudioFlowRun::query()
            ->where('dry_run', false)
            ->latest('started_at')
            ->limit(20);
    }
}
