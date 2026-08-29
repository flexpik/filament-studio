<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Support\Str;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
});

it('rejects requests to /flows when the API key lacks the flows scope', function () {
    $raw = Str::random(64);
    StudioApiKey::factory()->create([
        'key' => hash('sha256', $raw),
        'permissions' => ['*' => ['index', 'show']],
    ]);

    $this->withHeaders(['X-Api-Key' => $raw])
        ->getJson('/api/studio/flows')
        ->assertStatus(403);
});

it('allows requests when the API key has the flows scope', function () {
    $key = StudioApiKey::factory()->withFlowsScope()->create();

    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->getJson('/api/studio/flows')
        ->assertOk();
});
