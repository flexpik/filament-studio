<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    Bus::fake();
});

function makeApiKeyFlow(array $allowedIds): StudioFlow
{
    $flow = StudioFlow::factory()->create([
        'slug' => 'allowed-test',
        'status' => FlowStatus::Active,
        'webhook_auth_mode' => 'api_key',
        'webhook_allowed_studio_api_key_ids' => $allowedIds ?: null,
    ]);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => ['triggerType' => 'webhook', 'config' => []]]], 'edges' => []],
    ]);
    $flow->forceFill(['published_version_id' => $version->id])->save();

    return $flow;
}

function makeKey(string $plain): StudioApiKey
{
    return StudioApiKey::factory()->create(['key' => hash('sha256', $plain)]);
}

it('rejects globally-valid api keys not on the flow allowlist', function () {
    $allowedKey = makeKey('allowed-key');
    $rejectedKey = makeKey('rejected-key');

    makeApiKeyFlow([$allowedKey->id]);

    $this->withHeaders(['X-Api-Key' => 'rejected-key'])
        ->postJson('/api/studio/webhooks/allowed-test', [])
        ->assertStatus(403);

    $this->withHeaders(['X-Api-Key' => 'allowed-key'])
        ->postJson('/api/studio/webhooks/allowed-test', [])
        ->assertStatus(202);
});

it('allows any valid key when allowlist is empty', function () {
    $key = makeKey('any-key');
    makeApiKeyFlow([]);

    $this->withHeaders(['X-Api-Key' => 'any-key'])
        ->postJson('/api/studio/webhooks/allowed-test', [])
        ->assertStatus(202);
});

it('allows any valid key when allowlist is null', function () {
    $key = makeKey('any-key-2');
    makeApiKeyFlow([]);

    $this->withHeaders(['X-Api-Key' => 'any-key-2'])
        ->postJson('/api/studio/webhooks/allowed-test', [])
        ->assertStatus(202);
});
