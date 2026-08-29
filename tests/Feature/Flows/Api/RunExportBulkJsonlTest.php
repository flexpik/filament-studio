<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Models\StudioApiKey;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();

    $this->key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 1]);
    $this->flow = StudioFlow::factory()->active()->create(['tenant_id' => 1]);
    $version = StudioFlowVersion::factory()->for($this->flow, 'flow')->published()->create();
    $this->flow->forceFill(['published_version_id' => $version->id])->save();
    $this->flow->refresh();
});

it('streams runs as JSONL with one run per line', function () {
    StudioFlowRun::factory()->count(3)->for($this->flow, 'flow')->create([
        'started_at' => '2026-05-05 10:00:00',
        'dry_run' => false,
    ]);

    $response = $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->get("/api/studio/flows/{$this->flow->id}/runs/export?format=jsonl&from=2026-05-01&to=2026-05-11");

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/x-ndjson');

    $lines = array_values(array_filter(explode("\n", $response->streamedContent())));
    expect($lines)->toHaveCount(3);

    foreach ($lines as $line) {
        $decoded = json_decode($line, true);
        expect($decoded)->toHaveKey('id');
        expect($decoded)->toHaveKey('flow_id');
        expect($decoded)->toHaveKey('status');
    }
});

it('refuses ranges beyond the configured max (default 30 days)', function () {
    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->getJson("/api/studio/flows/{$this->flow->id}/runs/export?format=jsonl&from=2025-01-01&to=2026-05-11")
        ->assertStatus(422);
});

it('excludes dry runs', function () {
    StudioFlowRun::factory()->count(3)->for($this->flow, 'flow')->create([
        'started_at' => '2026-05-05 10:00:00',
        'dry_run' => true,
    ]);

    $response = $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->get("/api/studio/flows/{$this->flow->id}/runs/export?format=jsonl&from=2026-05-01&to=2026-05-11");

    $response->assertOk();

    $lines = array_values(array_filter(explode("\n", $response->streamedContent())));
    expect($lines)->toBeEmpty();
});

it('returns 404 for a flow from another tenant', function () {
    $otherKey = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 2]);

    $this->withHeaders(['X-Api-Key' => $otherKey->plain_text_key])
        ->getJson("/api/studio/flows/{$this->flow->id}/runs/export?format=jsonl&from=2026-05-01&to=2026-05-11")
        ->assertNotFound();
});

it('returns 422 when from or to is missing', function () {
    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->getJson("/api/studio/flows/{$this->flow->id}/runs/export?format=jsonl")
        ->assertStatus(422);
});

it('masks sensitive values in JSONL output', function () {
    StudioFlowRun::factory()->for($this->flow, 'flow')->create([
        'started_at' => '2026-05-05 10:00:00',
        'dry_run' => false,
        'trigger_payload' => ['password' => 'super-secret-val'],
    ]);

    $response = $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->get("/api/studio/flows/{$this->flow->id}/runs/export?format=jsonl&from=2026-05-01&to=2026-05-11");

    $content = $response->streamedContent();
    expect($content)->not->toContain('super-secret-val');
    expect($content)->toContain('***');
});
