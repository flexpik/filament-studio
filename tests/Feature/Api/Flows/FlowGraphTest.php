<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;
use Flexpik\FilamentStudio\Models\StudioApiKey;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    $this->key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 1]);
    $this->flow = StudioFlow::factory()->withPublishedVersion(['nodes' => [], 'edges' => []])->create(['tenant_id' => 1]);
});

it('GET /graph returns draft_graph (hydrated from published when null)', function () {
    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->getJson("/api/studio/flows/{$this->flow->id}/graph")
        ->assertOk()
        ->assertJsonPath('data.draft_graph.nodes', []);
});

it('PUT /graph writes draft_graph on the flow', function () {
    $graph = ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']]], 'edges' => []];

    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->putJson("/api/studio/flows/{$this->flow->id}/graph", ['graph' => $graph])
        ->assertOk();

    expect($this->flow->fresh()->draft_graph)->toBe($graph);
});

it('POST /publish creates a published version', function () {
    $this->flow->update(['draft_graph' => [
        'nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']]],
        'edges' => [],
    ]]);

    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->postJson("/api/studio/flows/{$this->flow->id}/publish", ['change_summary' => 'first'])
        ->assertStatus(201)
        ->assertJsonPath('data.published_at', fn ($v) => $v !== null);

    expect($this->flow->fresh()->publishedVersion)->not->toBeNull();
});

it('GET /versions returns version history newest-first', function () {
    $this->flow->update(['draft_graph' => ['nodes' => [], 'edges' => []]]);
    app(PublishFlowVersion::class)->publish($this->flow);

    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->getJson("/api/studio/flows/{$this->flow->id}/versions")
        ->assertOk()
        ->assertJsonPath('data.0.version', 2);
});
