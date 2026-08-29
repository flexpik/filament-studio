<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Models\StudioApiKey;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    $this->key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 1]);
});

it('creates a flow + initial draft version 1', function () {
    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->postJson('/api/studio/flows', [
            'name' => 'New Flow',
            'slug' => 'new-flow',
            'logging_mode' => 'full',
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'new-flow')
        ->assertJsonPath('data.status', FlowStatus::Inactive->value);

    $flow = StudioFlow::where('slug', 'new-flow')->first();
    expect($flow)->not->toBeNull();
    expect(
        StudioFlowVersion::where('flow_id', $flow->id)
            ->where('version', 1)
            ->whereNull('published_at')
            ->exists()
    )->toBeTrue();
});

it('rejects duplicate slug per tenant', function () {
    StudioFlow::factory()->create(['slug' => 'dup', 'tenant_id' => 1]);

    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->postJson('/api/studio/flows', ['name' => 'X', 'slug' => 'dup'])
        ->assertStatus(422);
});
