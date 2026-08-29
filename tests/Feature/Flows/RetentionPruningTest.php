<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Illuminate\Console\Scheduling\Schedule;

it('the flow scheduler hook registers model:prune for StudioFlowRun', function () {
    config()->set('filament-studio.flows.enabled', true);

    $schedule = app(Schedule::class);

    $hasPrune = collect($schedule->events())
        ->contains(fn ($e) => str_contains((string) $e->command, 'model:prune')
            && str_contains((string) $e->command, StudioFlowRun::class));

    expect($hasPrune)->toBeTrue();
});
