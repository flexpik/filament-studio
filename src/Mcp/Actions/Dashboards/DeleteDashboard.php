<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Dashboards;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Illuminate\Support\Facades\DB;

class DeleteDashboard
{
    public function __invoke(string $slug, ?int $tenantId): void
    {
        $dashboard = StudioDashboard::query()->forTenant($tenantId)->where('slug', $slug)->first();
        if ($dashboard === null) {
            throw new StudioNotFoundException('dashboard', $slug);
        }

        DB::transaction(function () use ($dashboard) {
            StudioPanel::query()->where('dashboard_id', $dashboard->id)->delete();
            $dashboard->delete();
        });
    }
}
