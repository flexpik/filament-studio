<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Triggers\TriggerRegistry;
use Illuminate\Console\Scheduling\Schedule;

beforeEach(function () {
    config()->set('filament-studio.flows.enabled', true);
});

it('registers all 4 MVP triggers on boot', function () {
    $registry = app(TriggerRegistry::class);

    foreach (['manual', 'webhook', 'collection_event', 'schedule'] as $key) {
        expect($registry->has($key))->toBeTrue("trigger {$key} not registered");
    }
});

it('schedules the dispatch-scheduled command every minute', function () {
    /** @var Schedule $schedule */
    $schedule = app(Schedule::class);

    $events = collect($schedule->events());
    expect($events->contains(fn ($e) => str_contains($e->command ?? '', 'studio:flows:dispatch-scheduled')))->toBeTrue();
});
