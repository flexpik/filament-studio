<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages\ViewFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRunStep;
use Livewire\Livewire;

it('renders step outputs with sensitive values masked', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));

    $flow = StudioFlow::factory()->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'status' => FlowRunStatus::Completed,
    ]);

    StudioFlowRunStep::factory()->for($run, 'run')->create([
        'operation_key' => 'op_sensitive',
        'status' => FlowRunStepStatus::Completed,
        'input' => ['username' => 'alice', 'password' => 'hunter2'],
        'output' => ['token' => 'my-secret-jwt', 'result' => 'ok'],
    ]);

    Livewire::test(ViewFlowRun::class, ['record' => $flow->id, 'runId' => $run->id])
        ->assertSee('***')
        ->assertSee('alice')
        ->assertSee('ok')
        ->assertDontSee('hunter2')
        ->assertDontSee('my-secret-jwt');
});
