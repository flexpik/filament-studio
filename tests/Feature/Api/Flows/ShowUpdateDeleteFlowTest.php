<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Models\StudioApiKey;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    $this->key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 1]);
    $this->flow = StudioFlow::factory()->create(['tenant_id' => 1]);
});

it('show returns the flow', function () {
    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->getJson("/api/studio/flows/{$this->flow->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $this->flow->id);
});

it('update changes metadata', function () {
    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->putJson("/api/studio/flows/{$this->flow->id}", ['name' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed');
});

it('delete removes a flow with no active runs', function () {
    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->deleteJson("/api/studio/flows/{$this->flow->id}")
        ->assertNoContent();

    expect(StudioFlow::find($this->flow->id))->toBeNull();
});

it('delete is blocked when active runs exist', function () {
    $version = StudioFlowVersion::factory()->for($this->flow, 'flow')->create();
    StudioFlowRun::factory()->for($this->flow, 'flow')->create([
        'flow_version_id' => $version->id,
        'status' => FlowRunStatus::Running,
    ]);

    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->deleteJson("/api/studio/flows/{$this->flow->id}")
        ->assertStatus(409);
});
