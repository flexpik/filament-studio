<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Models\StudioApiKey;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    $this->key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 1]);
});

it('returns paginated flows belonging to the api key tenant', function () {
    StudioFlow::factory()->count(3)->create(['tenant_id' => 1]);
    StudioFlow::factory()->create(['tenant_id' => 2]);

    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->getJson('/api/studio/flows')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'status', 'logging_mode']], 'meta']);
});

it('filters by status', function () {
    StudioFlow::factory()->count(2)->create(['tenant_id' => 1, 'status' => FlowStatus::Active]);
    StudioFlow::factory()->create(['tenant_id' => 1, 'status' => FlowStatus::Inactive]);

    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->getJson('/api/studio/flows?status=active')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});
