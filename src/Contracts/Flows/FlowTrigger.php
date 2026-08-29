<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Contracts\Flows;

use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;

interface FlowTrigger
{
    public function register(StudioFlowVersion $version): void;

    public function unregister(StudioFlowVersion $version): void;
}
