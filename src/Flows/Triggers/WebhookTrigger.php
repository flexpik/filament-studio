<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Triggers;

use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Illuminate\Support\Str;

class WebhookTrigger implements FlowTrigger
{
    public function register(StudioFlowVersion $version): void
    {
        $authMode = $this->triggerConfig($version)['auth_mode'] ?? 'none';

        if (in_array($authMode, ['hmac', 'bearer'], true)) {
            $flow = $version->flow;
            if ($flow->webhook_secret === null) {
                $flow->forceFill(['webhook_secret' => Str::random(48)])->save();
            }
        }
    }

    public function unregister(StudioFlowVersion $version): void
    {
        $version->flow->forceFill(['webhook_secret' => null])->save();
    }

    private function triggerConfig(StudioFlowVersion $version): array
    {
        $node = collect($version->graph['nodes'] ?? [])->firstWhere('type', 'trigger');

        return $node['data']['config'] ?? [];
    }
}
