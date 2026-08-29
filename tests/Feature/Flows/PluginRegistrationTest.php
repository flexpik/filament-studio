<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\FilamentStudioPlugin;
use Flexpik\FilamentStudio\Flows\Operations\NoOpActivity;
use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Flexpik\FilamentStudio\Flows\Triggers\TriggerRegistry;
use Flexpik\FilamentStudio\Tests\Support\Flows\NullTrigger;

it('registers the OperationRegistry and TriggerRegistry as singletons', function () {
    expect(app(OperationRegistry::class))->toBe(app(OperationRegistry::class));
    expect(app(TriggerRegistry::class))->toBe(app(TriggerRegistry::class));
});

it('plugin static API registers a flow operation', function () {
    FilamentStudioPlugin::registerFlowOperation(
        key: 'plugin_noop',
        label: 'Plugin No-op',
        activity: NoOpActivity::class,
    );

    expect(app(OperationRegistry::class)->has('plugin_noop'))->toBeTrue();
});

it('plugin static API registers a flow trigger', function () {
    FilamentStudioPlugin::registerFlowTrigger(
        key: 'plugin_manual',
        label: 'Plugin Manual',
        trigger: NullTrigger::class,
    );

    expect(app(TriggerRegistry::class)->has('plugin_manual'))->toBeTrue();
});
