<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages\ViewFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRunStep;
use Livewire\Livewire;

it('groups attempts under their step when attempt_number > 1', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));

    $flow = StudioFlow::factory()->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'status' => FlowRunStatus::Completed,
    ]);

    $now = now();

    foreach ([1, 2, 3] as $attempt) {
        StudioFlowRunStep::factory()->for($run, 'run')->create([
            'operation_key' => 'op_a',
            'attempt_number' => $attempt,
            'status' => $attempt < 3 ? FlowRunStepStatus::Failed : FlowRunStepStatus::Completed,
            'started_at' => $now->copy()->addMilliseconds($attempt * 100),
            'finished_at' => $now->copy()->addMilliseconds($attempt * 100 + 50),
        ]);
    }

    Livewire::test(ViewFlowRun::class, ['record' => $flow->id, 'runId' => $run->id])
        ->assertSee('Attempt 1')
        ->assertSee('Attempt 2')
        ->assertSee('Attempt 3');
});
