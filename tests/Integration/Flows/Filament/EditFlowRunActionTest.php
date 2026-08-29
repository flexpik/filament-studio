<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages\EditFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Livewire\Livewire;

it('Run action dispatches a new run when flow is active', function () {
    $this->actingAs($this->makeUserWith(['view_flows', 'update_flows', 'run_flows']));
    $flow = StudioFlow::factory()->active()->withPublishedVersion()->create();

    expect(StudioFlowRun::where('flow_id', $flow->id)->count())->toBe(0);

    Livewire::test(EditFlow::class, ['record' => $flow->id])
        ->callAction('run')
        ->assertHasNoActionErrors();

    expect(StudioFlowRun::where('flow_id', $flow->id)->count())->toBe(1);
});

it('Run action is disabled when flow is inactive', function () {
    $this->actingAs($this->makeUserWith(['view_flows', 'update_flows', 'run_flows']));
    $flow = StudioFlow::factory()->create(['status' => FlowStatus::Inactive]);

    Livewire::test(EditFlow::class, ['record' => $flow->id])
        ->assertActionDisabled('run');
});
