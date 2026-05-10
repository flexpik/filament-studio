<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Panels;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioPanel;

class DeletePanel
{
    public function __invoke(int $id, ?int $tenantId): void
    {
        $panel = StudioPanel::query()->where('tenant_id', $tenantId)->find($id);
        if ($panel === null) {
            throw new StudioNotFoundException('panel', (string) $id);
        }
        $panel->delete();
    }
}
