<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;

it('the design page renders a #flow-canvas-root mount with required data attributes', function () {
    $this->withoutVite();
    $this->actingAs($this->makeUserWith(['view_flows', 'update_flows']));
    $flow = StudioFlow::factory()->create();

    $response = $this->get(route('filament.admin.resources.flows.design', ['record' => $flow->id]));

    $response->assertOk();
    $response->assertSee('id="flow-canvas-root"', false);
    $response->assertSee('data-flow-id="'.$flow->id.'"', false);
    $response->assertSee('data-api-base="/api/studio"', false);
    $response->assertSee('data-flow-name="'.$flow->name.'"', false);
});

it('forbids users without update_flows', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));
    $flow = StudioFlow::factory()->create();

    $this->get(route('filament.admin.resources.flows.design', ['record' => $flow->id]))->assertForbidden();
});
