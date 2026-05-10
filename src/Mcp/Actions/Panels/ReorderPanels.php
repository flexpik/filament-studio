<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Panels;

use Flexpik\FilamentStudio\Mcp\Exceptions\IntegrityException;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReorderPanels
{
    /** @param array<string, mixed> $input */
    public function __invoke(array $input, int $tenantId): int
    {
        $data = Validator::validate($input, [
            'dashboard_slug' => ['required', 'string'],
            'panel_ids' => ['required', 'array', 'min:1'],
            'panel_ids.*' => ['integer'],
        ]);

        $dash = StudioDashboard::query()->forTenant($tenantId)->where('slug', $data['dashboard_slug'])->first();
        if ($dash === null) {
            throw new StudioNotFoundException('dashboard', $data['dashboard_slug']);
        }

        $existing = StudioPanel::query()
            ->where('dashboard_id', $dash->id)
            ->whereIn('id', $data['panel_ids'])
            ->pluck('id')->all();

        if (count($existing) !== count($data['panel_ids'])) {
            throw IntegrityException::duplicate('panel_ids', 'some_ids_not_found');
        }

        return DB::transaction(function () use ($data, $dash) {
            foreach ($data['panel_ids'] as $i => $id) {
                StudioPanel::query()
                    ->where('id', $id)
                    ->where('dashboard_id', $dash->id)
                    ->update(['grid_order' => $i, 'sort_order' => $i]);
            }

            return count($data['panel_ids']);
        });
    }
}
