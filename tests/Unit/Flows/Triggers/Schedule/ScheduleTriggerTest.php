<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Triggers\Schedule\ScheduleTrigger;

it('register and unregister are no-ops (DispatchScheduledFlowsCommand polls)', function () {
    $version = StudioFlowVersion::factory()->for(StudioFlow::factory()->create(), 'flow')->create([
        'graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => [
            'triggerType' => 'schedule', 'config' => ['cron' => '0 * * * *', 'timezone' => 'UTC'],
        ]]], 'edges' => []],
    ]);

    $trigger = app(ScheduleTrigger::class);
    $trigger->register($version);
    $trigger->unregister($version);

    expect(true)->toBeTrue();
});

it('rejects an invalid cron expression on register', function () {
    $version = StudioFlowVersion::factory()->for(StudioFlow::factory()->create(), 'flow')->create([
        'graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => [
            'triggerType' => 'schedule', 'config' => ['cron' => 'not-a-cron'],
        ]]], 'edges' => []],
    ]);

    app(ScheduleTrigger::class)->register($version);
})->throws(InvalidArgumentException::class);
