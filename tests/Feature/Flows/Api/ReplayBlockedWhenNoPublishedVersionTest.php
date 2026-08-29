<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Models\StudioApiKey;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();

    $this->key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 1]);
    // Flow with no published version (only a draft — published_version_id stays null)
    $this->flow = StudioFlow::factory()->active()->create(['tenant_id' => 1]);
    $this->run = StudioFlowRun::factory()->for($this->flow, 'flow')->create([
        'trigger_type' => 'manual',
        'trigger_payload' => ['x' => 1],
    ]);
});

it('returns 422 with explicit reason when there is no current published version', function () {
    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->postJson("/api/studio/flows/runs/{$this->run->id}/replay")
        ->assertStatus(422)
        ->assertJsonPath('errors.replay.0', 'No current published version');
});
