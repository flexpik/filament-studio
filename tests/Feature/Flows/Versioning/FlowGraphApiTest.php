<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;
use Flexpik\FilamentStudio\Models\StudioApiKey;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
});

it('PUT /flows/{id}/graph writes draft_graph only', function () {
    $key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => null]);
    $flow = StudioFlow::factory()->create();

    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->putJson("/api/studio/flows/{$flow->id}/graph", ['graph' => ['nodes' => [['id' => 'a']], 'edges' => []]])
        ->assertOk();

    expect($flow->fresh()->draft_graph)->toBe(['nodes' => [['id' => 'a']], 'edges' => []])
        ->and($flow->fresh()->published_version_id)->toBeNull();
});

it('POST /flows/{id}/publish mints v1 and points published_version_id', function () {
    $key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => null]);
    $flow = StudioFlow::factory()->create(['draft_graph' => ['nodes' => [], 'edges' => []]]);

    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->postJson("/api/studio/flows/{$flow->id}/publish", ['change_summary' => 'go'])
        ->assertStatus(201)
        ->assertJsonPath('data.version', 1)
        ->assertJsonPath('data.change_summary', 'go');

    expect($flow->fresh()->published_version_id)->not->toBeNull();
});

it('GET /flows/{id}/versions returns descending list', function () {
    $key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => null]);
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $flow->update(['draft_graph' => ['nodes' => [], 'edges' => []]]);
    app(PublishFlowVersion::class)->publish($flow);

    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->getJson("/api/studio/flows/{$flow->id}/versions")
        ->assertOk()
        ->assertJsonPath('data.0.version', 2)
        ->assertJsonPath('data.1.version', 1);
});

it('GET /flows/{id}/versions/{versionId} returns the graph', function () {
    $key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => null]);
    $flow = StudioFlow::factory()->withPublishedVersion(['nodes' => [['id' => 'v1']], 'edges' => []])->create();
    $v = $flow->publishedVersion;

    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->getJson("/api/studio/flows/{$flow->id}/versions/{$v->id}")
        ->assertOk()
        ->assertJsonPath('data.graph.nodes.0.id', 'v1');
});

it('POST /flows/{id}/versions/{versionId}/rollback creates a new version', function () {
    $key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => null]);
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $v1 = $flow->publishedVersion;
    $flow->update(['draft_graph' => ['nodes' => [['id' => 'v2']], 'edges' => []]]);
    app(PublishFlowVersion::class)->publish($flow);

    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->postJson("/api/studio/flows/{$flow->id}/versions/{$v1->id}/rollback")
        ->assertStatus(201)
        ->assertJsonPath('data.version', 3);
});
