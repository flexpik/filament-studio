<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Models\StudioApiKey;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();

    $this->key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => null]);
    $this->flow = StudioFlow::factory()->create([
        'draft_graph' => ['nodes' => [['id' => 't1', 'type' => 'trigger', 'data' => []]], 'edges' => []],
    ]);
});

it('passes dry_run=true through the test-run endpoint to the run record', function () {
    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->postJson("/api/studio/flows/{$this->flow->id}/test", [
            'payload' => ['foo' => 'bar'],
            'dry_run' => true,
        ])
        ->assertStatus(200);

    $run = StudioFlowRun::query()->where('flow_id', $this->flow->id)->latest('id')->first();

    expect($run)->not->toBeNull()
        ->and($run->dry_run)->toBeTrue();
});

it('defaults dry_run to false when not provided', function () {
    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->postJson("/api/studio/flows/{$this->flow->id}/test", [
            'payload' => ['foo' => 'bar'],
        ])
        ->assertStatus(200);

    $run = StudioFlowRun::query()->where('flow_id', $this->flow->id)->latest('id')->first();

    expect($run)->not->toBeNull()
        ->and($run->dry_run)->toBeFalse();
});
