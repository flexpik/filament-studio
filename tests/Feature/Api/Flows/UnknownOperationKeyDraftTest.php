<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Models\StudioApiKey;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    $this->key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 1]);
});

it('saves a draft graph referencing an unregistered op key', function () {
    $flow = StudioFlow::factory()->create(['tenant_id' => 1]);

    $graph = [
        'nodes' => [
            ['id' => 'trigger_1', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
            ['id' => 'node_bad', 'type' => 'operation', 'data' => ['operationType' => 'not_yet_installed_op', 'config' => []]],
        ],
        'edges' => [],
    ];

    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->putJson("/api/studio/flows/{$flow->id}/graph", ['graph' => $graph])
        ->assertSuccessful();

    expect($flow->fresh()->draft_graph)->toEqual($graph);
});

it('refuses to publish a draft with an unknown op key', function () {
    $flow = StudioFlow::factory()->create([
        'tenant_id' => 1,
        'draft_graph' => [
            'nodes' => [
                ['id' => 'trigger_1', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'node_bad', 'type' => 'operation', 'data' => ['operationType' => 'not_yet_installed_op', 'config' => []]],
            ],
            'edges' => [],
        ],
    ]);

    $response = $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->postJson("/api/studio/flows/{$flow->id}/publish");

    $response->assertStatus(422);
    expect($response->json('errors'))->toHaveKey('graph');
    expect($response->json('errors.graph.0'))->toContain('not_yet_installed_op');
});
