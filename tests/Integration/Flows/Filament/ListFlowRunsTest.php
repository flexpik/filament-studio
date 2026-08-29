<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages\ListFlowRuns;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Livewire\Livewire;

it('lists runs for the given flow newest first', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));
    $flow = StudioFlow::factory()->create();
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->create();
    StudioFlowRun::factory()->count(3)->for($flow, 'flow')->create(['flow_version_id' => $version->id]);

    Livewire::test(ListFlowRuns::class, ['record' => $flow->id])
        ->assertCanSeeTableRecords(StudioFlowRun::query()->where('flow_id', $flow->id)->get());
});
