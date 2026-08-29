<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages\ListFlowRuns;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Livewire\Livewire;

it('renders an Export runs header action', function () {
    $this->actingAs($this->makeUserWith(['view_flows', 'export_flows']));
    $flow = StudioFlow::factory()->create();

    Livewire::test(ListFlowRuns::class, ['record' => $flow->id])
        ->assertActionExists('exportRuns');
});

it('hides the action for users without export_flows', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));
    $flow = StudioFlow::factory()->create();

    Livewire::test(ListFlowRuns::class, ['record' => $flow->id])
        ->assertActionHidden('exportRuns');
});
