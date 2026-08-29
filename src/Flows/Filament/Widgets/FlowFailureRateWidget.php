<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Illuminate\Support\Facades\Cache;

class FlowFailureRateWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $rate = $this->computeFailureRate();
        $chart = $this->computeSparkline();

        return [
            Stat::make('Failure Rate (24h)', $rate)
                ->description('Failed runs in the last 24 hours')
                ->color('danger')
                ->chart($chart),
        ];
    }

    public function computeFailureRate(): string
    {
        return Cache::remember('flow_failure_rate_'.($this->getTenantId() ?? 'global'), 60, function () {
            $base = StudioFlowRun::query()
                ->where('dry_run', false)
                ->where('started_at', '>=', now()->subDay());

            $total = (clone $base)->count();

            if ($total === 0) {
                return '0%';
            }

            $failed = (clone $base)->where('status', 'failed')->count();

            return round(($failed / $total) * 100).'%';
        });
    }

    /**
     * 7-day sparkline: failure count per day.
     *
     * @return array<int>
     */
    protected function computeSparkline(): array
    {
        return Cache::remember('flow_failure_sparkline_'.($this->getTenantId() ?? 'global'), 60, function () {
            $rows = StudioFlowRun::query()
                ->where('dry_run', false)
                ->where('status', 'failed')
                ->where('started_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(started_at) as day, COUNT(*) as count')
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('count', 'day');

            $chart = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = now()->subDays($i)->toDateString();
                $chart[] = (int) ($rows[$day] ?? 0);
            }

            return $chart;
        });
    }

    protected function getTenantId(): ?string
    {
        return null;
    }
}
