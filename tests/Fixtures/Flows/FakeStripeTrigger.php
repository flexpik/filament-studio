<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Tests\Fixtures\Flows;

use Flexpik\FilamentStudio\Contracts\Flows\FlowTrigger;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;

class FakeStripeTrigger implements FlowTrigger
{
    public function register(StudioFlowVersion $version): void {}

    public function unregister(StudioFlowVersion $version): void {}
}
