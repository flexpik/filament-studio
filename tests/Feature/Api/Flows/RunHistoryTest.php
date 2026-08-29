<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRunStep;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Models\StudioApiKey;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    $this->key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 1]);
    $this->flow = StudioFlow::factory()->create(['tenant_id' => 1]);
    $this->version = StudioFlowVersion::factory()->for($this->flow, 'flow')->published()->create();
});

it('GET /runs returns paginated runs newest first', function () {
    StudioFlowRun::factory()->count(3)->for($this->flow, 'flow')->create(['flow_version_id' => $this->version->id]);

    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->getJson("/api/studio/flows/{$this->flow->id}/runs")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('GET /runs/{runId} returns run with steps', function () {
    $run = StudioFlowRun::factory()->for($this->flow, 'flow')->create(['flow_version_id' => $this->version->id]);
    StudioFlowRunStep::factory()->for($run, 'run')->count(2)->create();

    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->getJson("/api/studio/flows/{$this->flow->id}/runs/{$run->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data.steps');
});

it('POST /runs/{runId}/cancel marks a running run cancelled', function () {
    $run = StudioFlowRun::factory()->for($this->flow, 'flow')->create([
        'flow_version_id' => $this->version->id,
        'status' => FlowRunStatus::Running,
    ]);

    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->postJson("/api/studio/flows/runs/{$run->id}/cancel")
        ->assertOk();

    expect($run->fresh()->status)->toBe(FlowRunStatus::Cancelled);
});

it('cancel rejects already-terminal runs', function () {
    $run = StudioFlowRun::factory()->for($this->flow, 'flow')->create([
        'flow_version_id' => $this->version->id,
        'status' => FlowRunStatus::Completed,
    ]);

    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->postJson("/api/studio/flows/runs/{$run->id}/cancel")
        ->assertStatus(409);
});
