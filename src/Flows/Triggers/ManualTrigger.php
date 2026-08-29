<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Triggers;

use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;

class ManualTrigger implements FlowTrigger
{
    public function register(StudioFlowVersion $version): void
    {
        // Manual triggers are always available; nothing to register.
    }

    public function unregister(StudioFlowVersion $version): void
    {
        // No-op.
    }
}
