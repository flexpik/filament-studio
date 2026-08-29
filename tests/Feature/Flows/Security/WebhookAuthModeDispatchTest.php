<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Jobs\ExecuteFlowJob;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    Bus::fake();
    config()->set('filament-studio.flows.webhook_timestamp_window_seconds', 300);
});

function makeActiveFlow(string $authMode, array $extra = []): StudioFlow
{
    $flow = StudioFlow::factory()->create(array_merge([
        'slug' => 'test-'.str_replace('_', '-', $authMode),
        'status' => FlowStatus::Active,
        'webhook_auth_mode' => $authMode,
        'webhook_secret' => 'secret123',
    ], $extra));

    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => [
            'triggerType' => 'webhook',
            'config' => [],
        ]]], 'edges' => []],
    ]);
    $flow->forceFill(['published_version_id' => $version->id])->save();

    return $flow;
}

// --- HMAC mode ---
it('hmac: valid signature and timestamp returns 202', function () {
    $flow = makeActiveFlow('hmac');
    $body = '{"event":"test"}';
    $ts = (string) time();
    $sig = hash_hmac('sha256', $ts.'.'.$body, 'secret123');

    $this->call('POST', '/api/studio/webhooks/test-hmac', [], [], [],
        ['HTTP_X_STUDIO_SIGNATURE' => $sig, 'HTTP_X_STUDIO_TIMESTAMP' => $ts, 'CONTENT_TYPE' => 'application/json'],
        $body
    )->assertStatus(202);
    Bus::assertDispatched(ExecuteFlowJob::class);
});

it('hmac: missing X-Studio-Signature returns 401', function () {
    $flow = makeActiveFlow('hmac');
    $this->postJson('/api/studio/webhooks/test-hmac', [])->assertStatus(401);
});

// --- API key mode ---
it('api_key: missing X-Api-Key returns 401', function () {
    makeActiveFlow('api_key');
    $this->postJson('/api/studio/webhooks/test-api-key', [])->assertStatus(401);
});

it('api_key: valid key returns 202', function () {
    makeActiveFlow('api_key');
    $plainKey = 'my-plain-test-key-12345';
    StudioApiKey::factory()->create(['key' => hash('sha256', $plainKey)]);

    $this->withHeaders(['X-Api-Key' => $plainKey])
        ->postJson('/api/studio/webhooks/test-api-key', [])
        ->assertStatus(202);
    Bus::assertDispatched(ExecuteFlowJob::class);
});

// --- None mode ---
it('none: any payload returns 202', function () {
    makeActiveFlow('none');
    $this->postJson('/api/studio/webhooks/test-none', ['arbitrary' => 'data'])->assertStatus(202);
    Bus::assertDispatched(ExecuteFlowJob::class);
});
