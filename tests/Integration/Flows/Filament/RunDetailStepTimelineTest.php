<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages\ViewFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRunStep;
use Livewire\Livewire;

it('renders step timeline with status, duration, and ordering', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));

    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'status' => FlowRunStatus::Completed,
    ]);

    $now = now();

    StudioFlowRunStep::factory()->for($run, 'run')->create([
        'operation_key' => 'op_a',
        'status' => FlowRunStepStatus::Completed,
        'started_at' => $now->copy()->subMilliseconds(300),
        'finished_at' => $now->copy()->subMilliseconds(200),
    ]);

    StudioFlowRunStep::factory()->for($run, 'run')->create([
        'operation_key' => 'op_b',
        'status' => FlowRunStepStatus::Failed,
        'started_at' => $now->copy()->subMilliseconds(150),
        'finished_at' => $now->copy()->subMilliseconds(100),
    ]);

    StudioFlowRunStep::factory()->for($run, 'run')->create([
        'operation_key' => 'op_c',
        'status' => FlowRunStepStatus::Skipped,
        'started_at' => $now->copy()->subMilliseconds(50),
        'finished_at' => $now->copy(),
    ]);

    Livewire::test(ViewFlowRun::class, ['record' => $flow->id, 'runId' => $run->id])
        ->assertSee('op_a')
        ->assertSee('op_b')
        ->assertSee('op_c')
        ->assertSeeHtml('data-status="completed"')
        ->assertSeeHtml('data-status="failed"')
        ->assertSeeHtml('data-status="skipped"')
        ->assertSee('ms');
});

it('renders the "Ran on version vN" pill', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));

    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $version = $flow->publishedVersion;

    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => $version->id,
        'status' => FlowRunStatus::Completed,
    ]);

    Livewire::test(ViewFlowRun::class, ['record' => $flow->id, 'runId' => $run->id])
        ->assertSee("Ran on version v{$version->version}");
});

it('shows "Ran on draft (inline snapshot)" for test runs', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));

    $flow = StudioFlow::factory()->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => null,
        'status' => FlowRunStatus::Completed,
    ]);

    Livewire::test(ViewFlowRun::class, ['record' => $flow->id, 'runId' => $run->id])
        ->assertSee('Ran on draft (inline snapshot)');
});
