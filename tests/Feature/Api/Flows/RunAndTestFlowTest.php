<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Jobs\ExecuteFlowJob;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    $this->key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 1]);
    $this->flow = StudioFlow::factory()->active()->create([
        'tenant_id' => 1,
        'draft_graph' => ['nodes' => [['id' => 't1', 'type' => 'trigger', 'data' => []]], 'edges' => []],
    ]);
    $version = StudioFlowVersion::factory()->for($this->flow, 'flow')->published()->create();
    $this->flow->forceFill(['published_version_id' => $version->id])->save();
});

it('POST /run dispatches async and returns 202 with run id', function () {
    Bus::fake();

    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->postJson("/api/studio/flows/{$this->flow->id}/run", ['payload' => ['x' => 1]])
        ->assertStatus(202)
        ->assertJsonStructure(['data' => ['id', 'status', 'trigger_type']]);

    Bus::assertDispatched(ExecuteFlowJob::class);
});

it('POST /run rejects an inactive flow', function () {
    $this->flow->forceFill(['status' => FlowStatus::Inactive])->save();

    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->postJson("/api/studio/flows/{$this->flow->id}/run", [])
        ->assertStatus(409);
});

it('POST /test runs asynchronously and returns the pending run', function () {
    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->postJson("/api/studio/flows/{$this->flow->id}/test", ['payload' => []])
        ->assertOk()
        ->assertJsonPath('data.status', FlowRunStatus::Pending->value);
});
