<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Services;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Triggers\TriggerRegistry;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PublishFlowVersion
{
    public function __construct(
        private TriggerRegistry $triggers,
        private AssertCanPublishDangerousGraph $gate,
        private FlowGraphValidator $validator,
    ) {}

    public function publish(StudioFlow $flow, ?string $changeSummary = null, ?string $publishedBy = null): StudioFlowVersion
    {
        if ($flow->draft_graph === null) {
            throw new RuntimeException('no_draft_to_publish');
        }

        $this->gate->assert($flow->draft_graph, auth()->user());
        $this->validator->validate($flow->draft_graph);

        return DB::transaction(function () use ($flow, $changeSummary, $publishedBy) {
            $previous = $flow->publishedVersion;
            if ($previous !== null) {
                $this->unregisterTrigger($previous);
            }

            $nextVersion = ((int) $flow->versions()->max('version')) + 1;

            $version = StudioFlowVersion::create([
                'flow_id' => $flow->id,
                'version' => $nextVersion,
                'graph' => $flow->draft_graph,
                'published_at' => now(),
                'change_summary' => $changeSummary,
                'published_by' => $publishedBy,
                'created_at' => now(),
            ]);

            $flow->forceFill([
                'published_version_id' => $version->id,
                'draft_graph' => null,
                'draft_updated_at' => null,
            ])->save();

            $this->registerTrigger($version);

            return $version;
        });
    }

    private function registerTrigger(StudioFlowVersion $version): void
    {
        $type = $this->triggerType($version);
        if ($type !== null && $this->triggers->has($type)) {
            $this->triggers->resolve($type)->register($version);
        }
    }

    private function unregisterTrigger(StudioFlowVersion $version): void
    {
        $type = $this->triggerType($version);
        if ($type !== null && $this->triggers->has($type)) {
            $this->triggers->resolve($type)->unregister($version);
        }
    }

    private function triggerType(StudioFlowVersion $version): ?string
    {
        $node = collect($version->graph['nodes'] ?? [])->firstWhere('type', 'trigger');

        return $node['data']['triggerType'] ?? null;
    }
}
