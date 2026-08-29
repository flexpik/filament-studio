<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Flexpik\FilamentStudio\Flows\Triggers\Schedule\CronEvaluator;

it('returns true when the expression matches the current minute', function () {
    $now = CarbonImmutable::create(2026, 5, 10, 9, 0, 0);

    expect((new CronEvaluator)->isDue('0 9 * * *', 'UTC', $now))->toBeTrue();
});

it('returns false when expression does not match', function () {
    $now = CarbonImmutable::create(2026, 5, 10, 8, 0, 0);

    expect((new CronEvaluator)->isDue('0 9 * * *', 'UTC', $now))->toBeFalse();
});

it('respects timezone', function () {
    $now = CarbonImmutable::create(2026, 5, 10, 7, 0, 0, 'UTC'); // 09:00 in CEST

    expect((new CronEvaluator)->isDue('0 9 * * *', 'Europe/Berlin', $now))->toBeTrue();
});
