<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Triggers\ManualTrigger;

it('register and unregister are no-ops (manual is always available)', function () {
    $version = StudioFlowVersion::factory()->make();
    $trigger = new ManualTrigger;

    $trigger->register($version);
    $trigger->unregister($version);

    expect(true)->toBeTrue();
});
