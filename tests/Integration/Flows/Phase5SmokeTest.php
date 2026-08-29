<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;

it('phase 5 smoke: admin can list, create, edit, design, view runs', function () {
    $this->withoutVite();
    $admin = $this->makeUserWith(['view_flows', 'create_flows', 'update_flows', 'delete_flows', 'run_flows']);
    $this->actingAs($admin);

    $this->get(FlowResource::getUrl('index'))->assertOk();
    $this->get(FlowResource::getUrl('create'))->assertOk();

    $flow = StudioFlow::factory()->create();
    $this->get(FlowResource::getUrl('edit', ['record' => $flow]))->assertOk();
    $this->get(FlowResource::getUrl('design', ['record' => $flow]))->assertOk();
    $this->get(FlowResource::getUrl('runs', ['record' => $flow]))->assertOk();
});
