<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Jobs\ExecuteFlowJob;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    Bus::fake();

    $this->key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 1]);
    $this->flow = StudioFlow::factory()->active()->create(['tenant_id' => 1]);
    $version = StudioFlowVersion::factory()->for($this->flow, 'flow')->published()->create();
    $this->flow->forceFill(['published_version_id' => $version->id])->save();
    $this->flow->refresh();

    $this->run = StudioFlowRun::factory()->for($this->flow, 'flow')->create([
        'flow_version_id' => $version->id,
        'trigger_type' => 'manual',
        'trigger_payload' => ['key' => 'original-value'],
    ]);
});

it('replays a run against the current published version with original payload', function () {
    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->postJson("/api/studio/flows/runs/{$this->run->id}/replay")
        ->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'status', 'trigger_type']]);

    $newRun = StudioFlowRun::query()
        ->where('flow_id', $this->flow->id)
        ->where('id', '!=', $this->run->id)
        ->first();

    expect($newRun)->not->toBeNull();
    expect($newRun->trigger_payload)->toBe(['key' => 'original-value']);
    expect($newRun->flow_version_id)->toBe($this->flow->published_version_id);
    expect($newRun->id)->not->toBe($this->run->id);

    Bus::assertDispatched(ExecuteFlowJob::class);
});

it('requires a valid API key with flows scope', function () {
    $this->withHeaders(['X-Api-Key' => 'invalid-key'])
        ->postJson("/api/studio/flows/runs/{$this->run->id}/replay")
        ->assertStatus(401);
});
