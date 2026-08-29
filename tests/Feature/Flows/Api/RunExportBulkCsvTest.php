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

it('streams CSV with header + run summary rows', function () {
    StudioFlowRun::factory()->count(2)->for($this->flow, 'flow')->create([
        'started_at' => '2026-05-05 10:00:00',
        'dry_run' => false,
    ]);

    $response = $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->get("/api/studio/flows/{$this->flow->id}/runs/export?format=csv&from=2026-05-01&to=2026-05-11");

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/csv');

    $rawLines = array_filter(explode("\n", $response->streamedContent()));
    $rawLines = array_values($rawLines);

    expect($rawLines[0])->toContain('id,flow_id,status');
    expect(count($rawLines))->toBe(3); // header + 2 data rows
});

it('produces only a header when no matching runs exist', function () {
    $response = $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->get("/api/studio/flows/{$this->flow->id}/runs/export?format=csv&from=2026-05-01&to=2026-05-11");

    $response->assertOk();
    $rawLines = array_filter(explode("\n", $response->streamedContent()));
    $rawLines = array_values($rawLines);

    expect(count($rawLines))->toBe(1); // only header
    expect($rawLines[0])->toContain('id,flow_id,status');
});

it('excludes dry runs from CSV', function () {
    StudioFlowRun::factory()->count(3)->for($this->flow, 'flow')->create([
        'started_at' => '2026-05-05 10:00:00',
        'dry_run' => true,
    ]);

    $response = $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->get("/api/studio/flows/{$this->flow->id}/runs/export?format=csv&from=2026-05-01&to=2026-05-11");

    $rawLines = array_filter(explode("\n", $response->streamedContent()));
    $rawLines = array_values($rawLines);

    // Only header row - no data rows
    expect(count($rawLines))->toBe(1);
});
