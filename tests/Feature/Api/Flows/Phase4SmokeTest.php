<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Models\StudioApiKey;

it('phase 4 smoke: create → save graph → publish → run → fetch run', function () {
    StudioFlowsApiRouteRegistrar::register();

    $key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 1]);
    $h = ['X-Api-Key' => $key->plain_text_key];

    // 1. Create flow
    $created = $this->withHeaders($h)
        ->postJson('/api/studio/flows', ['name' => 'Smoke', 'slug' => 'smoke-4'])
        ->assertCreated()
        ->json('data');

    // 2. Save graph with manual trigger + log_message operation
    $this->withHeaders($h)->putJson("/api/studio/flows/{$created['id']}/graph", [
        'graph' => [
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'op1', 'type' => 'operation', 'data' => [
                    'key' => 'op1',
                    'operationType' => 'log_message',
                    'config' => ['level' => 'info', 'message' => 'hi'],
                ]],
            ],
            'edges' => [['id' => 'e1', 'source' => 'trigger', 'target' => 'op1', 'sourceHandle' => 'success']],
        ],
    ])->assertOk();

    // 3. Test the draft (asynchronous)
    $run = $this->withHeaders($h)
        ->postJson("/api/studio/flows/{$created['id']}/test", ['payload' => []])
        ->assertOk()
        ->json('data');

    // 4. Fetch run detail (in test environment, job executes immediately)
    $this->withHeaders($h)
        ->getJson("/api/studio/flows/{$created['id']}/runs/{$run['id']}")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonCount(1, 'data.steps');

    // 5. Publish — returns 201 because a new StudioFlowVersion row is created
    $this->withHeaders($h)
        ->postJson("/api/studio/flows/{$created['id']}/publish")
        ->assertStatus(201);

    // 6. Set flow to active (API doesn't expose status update — set directly)
    StudioFlow::where('id', $created['id'])
        ->update(['status' => 'active']);
});
