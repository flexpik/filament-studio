<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Jobs\ExecuteFlowJob;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    $this->flow = StudioFlow::factory()->create([
        'slug' => 'incoming',
        'status' => FlowStatus::Active,
        'webhook_secret' => 'sssh',
        'webhook_auth_mode' => 'hmac',
    ]);
    $version = StudioFlowVersion::factory()->for($this->flow, 'flow')->published()->create([
        'graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => [
            'triggerType' => 'webhook',
            'config' => ['response_mode' => 'async'],
        ]]], 'edges' => []],
    ]);
    $this->flow->forceFill(['published_version_id' => $version->id])->save();
});

it('accepts a valid hmac-signed POST and dispatches the flow', function () {
    Bus::fake();
    $body = '{"hello":"world"}';
    $ts = (string) time();
    $sig = hash_hmac('sha256', $ts.'.'.$body, 'sssh');

    $this->call(
        'POST',
        '/api/studio/webhooks/incoming',
        [],
        [],
        [],
        ['HTTP_X_STUDIO_SIGNATURE' => $sig, 'HTTP_X_STUDIO_TIMESTAMP' => $ts, 'CONTENT_TYPE' => 'application/json'],
        $body,
    )->assertStatus(202);

    Bus::assertDispatched(ExecuteFlowJob::class);
});

it('rejects an invalid signature with 401', function () {
    $ts = (string) time();

    $this->withHeaders(['X-Studio-Signature' => 'wrong', 'X-Studio-Timestamp' => $ts])
        ->postJson('/api/studio/webhooks/incoming', ['x' => 1])
        ->assertStatus(401);
});

it('rejects when the flow is inactive', function () {
    $this->flow->forceFill(['status' => FlowStatus::Inactive])->save();

    $body = '{}';
    $ts = (string) time();
    $sig = hash_hmac('sha256', $ts.'.'.$body, 'sssh');

    $this->call(
        'POST',
        '/api/studio/webhooks/incoming',
        [],
        [],
        [],
        ['HTTP_X_STUDIO_SIGNATURE' => $sig, 'HTTP_X_STUDIO_TIMESTAMP' => $ts, 'CONTENT_TYPE' => 'application/json'],
        $body,
    )->assertStatus(409);
});

it('returns 404 for unknown slugs', function () {
    $this->postJson('/api/studio/webhooks/missing', [])->assertStatus(404);
});
