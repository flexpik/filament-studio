<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages\ViewFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs($this->makeUserWith(['view_flows', 'run_flows']));
});

it('renders a replay action on the run-detail page', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => $flow->published_version_id,
        'status' => FlowRunStatus::Completed,
    ]);

    Livewire::test(ViewFlowRun::class, ['record' => $flow->id, 'runId' => $run->id])
        ->assertActionExists('replay');
});

it('disables the Replay action when no current published version exists', function () {
    $flow = StudioFlow::factory()->create(); // no published version
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'status' => FlowRunStatus::Completed,
    ]);

    Livewire::test(ViewFlowRun::class, ['record' => $flow->id, 'runId' => $run->id])
        ->assertActionDisabled('replay');
});
