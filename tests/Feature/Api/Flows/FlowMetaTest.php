<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    $this->key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 1]);
});

it('GET /meta/operations lists registered operations', function () {
    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->getJson('/api/studio/flows/meta/operations')
        ->assertOk()
        ->assertJsonStructure(['data' => [['key', 'label', 'configSchema']]])
        ->assertJsonFragment(['key' => 'send_email']);
});

it('GET /meta/triggers lists registered triggers', function () {
    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->getJson('/api/studio/flows/meta/triggers')
        ->assertOk()
        ->assertJsonFragment(['key' => 'webhook']);
});

it('GET /meta/collections lists tenant collections + their fields', function () {
    $col = StudioCollection::factory()->create(['slug' => 'people', 'tenant_id' => 1]);
    StudioField::factory()->for($col, 'collection')->create(['column_name' => 'full_name', 'field_type' => 'text']);

    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->getJson('/api/studio/flows/meta/collections')
        ->assertOk()
        ->assertJsonFragment(['slug' => 'people'])
        ->assertJsonPath('data.0.fields.0.key', 'full_name');
});
