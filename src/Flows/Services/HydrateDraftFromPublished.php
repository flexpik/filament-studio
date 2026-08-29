<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Services;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;

class HydrateDraftFromPublished
{
    public function hydrate(StudioFlow $flow): StudioFlow
    {
        if ($flow->draft_graph !== null) {
            return $flow;
        }
        $published = $flow->publishedVersion;
        if ($published === null) {
            return $flow;
        }

        $flow->forceFill([
            'draft_graph' => $published->graph,
            'draft_updated_at' => now(),
        ])->save();

        return $flow;
    }
}
