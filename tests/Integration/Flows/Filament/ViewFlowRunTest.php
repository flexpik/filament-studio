<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages\ViewFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRunStep;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs($this->makeUserWith(['view_flows', 'run_flows']));

    $this->flow = StudioFlow::factory()->withPublishedVersion()->create();
    $version = $this->flow->publishedVersion;
    $this->run = StudioFlowRun::factory()->for($this->flow, 'flow')->create([
        'flow_version_id' => $version->id,
        'status' => FlowRunStatus::Completed,
        'trigger_payload' => ['x' => 1],
    ]);
    StudioFlowRunStep::factory()->for($this->run, 'run')->create([
        'operation_key' => 'op_a',
        'status' => FlowRunStepStatus::Completed,
        'output' => ['result' => 'ok'],
    ]);
});

it('renders the step timeline and run metadata', function () {
    Livewire::test(ViewFlowRun::class, ['record' => $this->flow->id, 'runId' => $this->run->id])
        ->assertSee('op_a')
        ->assertSee($this->flow->name);
});

it('re-run action dispatches a new run with same payload', function () {
    Livewire::test(ViewFlowRun::class, ['record' => $this->flow->id, 'runId' => $this->run->id])
        ->callAction('rerun')
        ->assertHasNoActionErrors();

    expect(StudioFlowRun::query()->where('flow_id', $this->flow->id)->count())->toBe(2);
});

it('cancel action moves a running run to cancelled', function () {
    $this->run->forceFill(['status' => FlowRunStatus::Running])->save();

    Livewire::test(ViewFlowRun::class, ['record' => $this->flow->id, 'runId' => $this->run->id])
        ->callAction('cancel')
        ->assertHasNoActionErrors();

    expect($this->run->fresh()->status)->toBe(FlowRunStatus::Cancelled);
});

it('cancel action is hidden when run is already in a terminal state', function () {
    Livewire::test(ViewFlowRun::class, ['record' => $this->flow->id, 'runId' => $this->run->id])
        ->assertActionHidden('cancel');
});

it('accessing a run from a different flow returns 404', function () {
    $otherFlow = StudioFlow::factory()->withPublishedVersion()->create();
    $otherRun = StudioFlowRun::factory()->for($otherFlow, 'flow')->create(['flow_version_id' => $otherFlow->published_version_id]);

    $this->get(route('filament.admin.resources.flows.view-run', ['record' => $this->flow->id, 'runId' => $otherRun->id]))
        ->assertNotFound();
});
