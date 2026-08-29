<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TopFailingFlowsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Flow'),
                Tables\Columns\TextColumn::make('failure_count')
                    ->label('Failures (24h)')
                    ->sortable(),
            ])
            ->paginated(false);
    }

    protected function getTableQuery(): Builder
    {
        return $this->topFailingFlowsQuery();
    }

    /**
     * Return the top failing flows with a failure_count attribute.
     */
    public function getTopFailingFlows(): Collection
    {
        return $this->topFailingFlowsQuery()->get();
    }

    /**
     * Rank flows by failed (non-dry-run) runs in the last 24h.
     *
     * The failure count is computed in a subquery and joined in, so the outer
     * `select flows.*` carries no GROUP BY — keeping the query valid under
     * MySQL/MariaDB ONLY_FULL_GROUP_BY (SQLite does not enforce it).
     */
    protected function topFailingFlowsQuery(): Builder
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');
        $flowsTable = $prefix.'flows';

        $failureCounts = StudioFlowRun::query()
            ->select('flow_id', DB::raw('COUNT(*) as failure_count'))
            ->where('status', 'failed')
            ->where('dry_run', false)
            ->where('started_at', '>=', now()->subDay())
            ->groupBy('flow_id');

        return StudioFlow::query()
            ->select("{$flowsTable}.*", 'failure_counts.failure_count')
            ->joinSub($failureCounts, 'failure_counts', 'failure_counts.flow_id', '=', "{$flowsTable}.id")
            ->orderByDesc('failure_count')
            ->orderBy("{$flowsTable}.id")
            ->limit(10);
    }
}
