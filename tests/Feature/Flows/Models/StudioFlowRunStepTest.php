<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRunStep;

it('casts status, input, output, and timestamps', function () {
    $step = StudioFlowRunStep::factory()->create([
        'status' => 'completed',
        'input' => ['x' => 1],
        'output' => ['y' => 2],
    ]);
    $step = $step->fresh();
    expect($step->status)->toBe(FlowRunStepStatus::Completed);
    expect($step->input)->toBe(['x' => 1]);
    expect($step->output)->toBe(['y' => 2]);
});

it('belongs to a run', function () {
    $run = StudioFlowRun::factory()->create();
    $step = StudioFlowRunStep::factory()->for($run, 'run')->create();
    expect($step->run->is($run))->toBeTrue();
});
