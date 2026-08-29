<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Models\StudioApiKey;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
});

it('stores draft_graph as inline_graph and leaves flow_version_id null', function () {
    $key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => null]);
    $flow = StudioFlow::factory()->create([
        'draft_graph' => ['nodes' => [['id' => 't1', 'type' => 'trigger', 'data' => []]], 'edges' => []],
    ]);

    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->postJson("/api/studio/flows/{$flow->id}/test", ['payload' => ['x' => 1]])
        ->assertOk();

    $run = StudioFlowRun::query()->where('flow_id', $flow->id)->latest()->first();
    expect($run->flow_version_id)->toBeNull()
        ->and($run->inline_graph)->toBe(['nodes' => [['id' => 't1', 'type' => 'trigger', 'data' => []]], 'edges' => []]);
});

it('test endpoint 422s when there is no draft to test', function () {
    $key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => null]);
    $flow = StudioFlow::factory()->create(['draft_graph' => null]);

    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->postJson("/api/studio/flows/{$flow->id}/test")
        ->assertStatus(422)
        ->assertJson(['error' => 'no_draft']);
});
