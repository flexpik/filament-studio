<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Enums\LoggingMode;

it('defines flow status cases', function () {
    expect(FlowStatus::cases())->toHaveCount(2);
    expect(FlowStatus::Active->value)->toBe('active');
    expect(FlowStatus::Inactive->value)->toBe('inactive');
});

it('defines logging modes', function () {
    expect(array_map(fn ($c) => $c->value, LoggingMode::cases()))
        ->toBe(['full', 'errors_only', 'disabled']);
});

it('defines flow run status terminal helper', function () {
    expect(FlowRunStatus::Pending->isTerminal())->toBeFalse();
    expect(FlowRunStatus::Running->isTerminal())->toBeFalse();
    expect(FlowRunStatus::Completed->isTerminal())->toBeTrue();
    expect(FlowRunStatus::Failed->isTerminal())->toBeTrue();
    expect(FlowRunStatus::Cancelled->isTerminal())->toBeTrue();
});

it('defines flow run step status', function () {
    expect(array_map(fn ($c) => $c->value, FlowRunStepStatus::cases()))
        ->toBe(['pending', 'running', 'completed', 'failed', 'skipped']);
});
