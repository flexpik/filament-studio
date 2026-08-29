<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Services;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use RuntimeException;

class RollbackFlowVersion
{
    public function __construct(private PublishFlowVersion $publisher) {}

    public function rollback(StudioFlow $flow, StudioFlowVersion $target, ?string $publishedBy = null): StudioFlowVersion
    {
        if ($target->flow_id !== $flow->id) {
            throw new RuntimeException('version_belongs_to_other_flow');
        }

        $flow->forceFill([
            'draft_graph' => $target->graph,
            'draft_updated_at' => now(),
        ])->save();

        return $this->publisher->publish(
            $flow->fresh(),
            changeSummary: "Restored from v{$target->version}",
            publishedBy: $publishedBy,
        );
    }
}
