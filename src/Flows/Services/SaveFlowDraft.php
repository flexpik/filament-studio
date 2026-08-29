<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Services;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;

class SaveFlowDraft
{
    /** @param  array<string, mixed>  $graph */
    public function save(StudioFlow $flow, array $graph): StudioFlow
    {
        $flow->forceFill([
            'draft_graph' => $graph,
            'draft_updated_at' => now(),
        ])->save();

        return $flow;
    }
}
