<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Tests\Support\Flows;

use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Triggers\FlowTrigger;

class NullTrigger implements FlowTrigger
{
    public function register(StudioFlowVersion $version): void {}

    public function unregister(StudioFlowVersion $version): void {}
}
