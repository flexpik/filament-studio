<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Illuminate\Support\Facades\Cache;

class FlowDurationWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        [$avg, $p95] = $this->computeDurations();

        return [
            Stat::make('Avg Duration (24h)', $avg)
                ->description('Average run duration'),
            Stat::make('P95 Duration (24h)', $p95)
                ->description('95th percentile run duration'),
        ];
    }

    /**
     * Compute avg and p95 duration strings for the last 24h (dry_run excluded).
     *
     * @return array{string, string}
     */
    public function computeDurations(): array
    {
        return Cache::remember('flow_duration_stats_'.($this->getTenantId() ?? 'global'), 60, function () {
            $durations = StudioFlowRun::query()
                ->where('dry_run', false)
                ->where('started_at', '>=', now()->subDay())
                ->whereNotNull('duration_ms')
                ->pluck('duration_ms')
                ->map(fn ($v) => (int) $v)
                ->sort()
                ->values()
                ->all();

            if (empty($durations)) {
                return ['0ms', '0ms'];
            }

            $avg = (int) round(array_sum($durations) / count($durations));
            $p95Index = (int) floor(0.95 * count($durations));
            // Clamp to last index
            $p95Index = min($p95Index, count($durations) - 1);
            $p95 = $durations[$p95Index];

            return [
                number_format($avg).'ms',
                number_format($p95).'ms',
            ];
        });
    }

    protected function getTenantId(): ?string
    {
        return null;
    }
}
