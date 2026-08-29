<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Contracts\Flows\FlowTrigger;
use Flexpik\FilamentStudio\Contracts\Flows\FlowTriggerConfig;
use Flexpik\FilamentStudio\Flows\Triggers\CollectionEventTrigger;
use Flexpik\FilamentStudio\Flows\Triggers\CollectionEventTriggerConfig;
use Flexpik\FilamentStudio\Flows\Triggers\ManualTrigger;
use Flexpik\FilamentStudio\Flows\Triggers\ManualTriggerConfig;
use Flexpik\FilamentStudio\Flows\Triggers\Schedule\ScheduleTrigger;
use Flexpik\FilamentStudio\Flows\Triggers\Schedule\ScheduleTriggerConfig;
use Flexpik\FilamentStudio\Flows\Triggers\WebhookTrigger;
use Flexpik\FilamentStudio\Flows\Triggers\WebhookTriggerConfig;

it('every built-in trigger implements the public contract', function () {
    foreach ([ManualTrigger::class, WebhookTrigger::class, CollectionEventTrigger::class, ScheduleTrigger::class] as $cls) {
        expect(is_subclass_of($cls, FlowTrigger::class))
            ->toBeTrue("Expected {$cls} to implement public FlowTrigger contract");
    }
});

it('every built-in trigger has a paired Config class implementing FlowTriggerConfig', function () {
    $map = [
        ManualTrigger::class => ManualTriggerConfig::class,
        WebhookTrigger::class => WebhookTriggerConfig::class,
        CollectionEventTrigger::class => CollectionEventTriggerConfig::class,
        ScheduleTrigger::class => ScheduleTriggerConfig::class,
    ];

    foreach ($map as $handler => $config) {
        expect(is_subclass_of($config, FlowTriggerConfig::class))
            ->toBeTrue("Expected {$config} to implement FlowTriggerConfig");
    }
});
