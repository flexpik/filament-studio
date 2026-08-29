<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Enums\WebhookAuthMode;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowAuditLog;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Security\Exceptions\InvalidWebhookSignatureException;
use Flexpik\FilamentStudio\Flows\Security\HmacWebhookVerifier;
use Flexpik\FilamentStudio\Flows\Security\MasksSensitiveValues;
use Flexpik\FilamentStudio\Flows\Services\Exceptions\CannotPublishDangerousFlowException;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;
use Flexpik\FilamentStudio\Flows\Services\RotateWebhookSecret;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    config()->set('filament-studio.flows.webhook_timestamp_window_seconds', 300);
    config()->set('filament-studio.flows.webhook_rate_limit_per_minute', 3);
    Bus::fake();
});

// Helper to create an active flow with a published version
function makeP8Flow(array $attributes = []): StudioFlow
{
    $flow = StudioFlow::factory()->create(array_merge([
        'status' => FlowStatus::Active,
        'webhook_auth_mode' => WebhookAuthMode::Hmac,
        'webhook_secret' => 'smoke-secret',
    ], $attributes));
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'webhook', 'config' => []]]], 'edges' => []],
    ]);
    $flow->forceFill(['published_version_id' => $version->id])->save();

    return $flow;
}

function makeP8Sig(string $body, string $secret = 'smoke-secret'): array
{
    $ts = (string) time();

    return ['sig' => hash_hmac('sha256', $ts.'.'.$body, $secret), 'ts' => $ts];
}

// 1. HMAC valid → 202 + run row created
it('HMAC valid signature → 202 and run row created', function () {
    $flow = makeP8Flow(['slug' => 'smoke-hmac-valid']);
    $body = '{"event":"smoke"}';
    ['sig' => $sig, 'ts' => $ts] = makeP8Sig($body);

    $this->call('POST', '/api/studio/webhooks/smoke-hmac-valid', [], [], [],
        ['HTTP_X_STUDIO_SIGNATURE' => $sig, 'HTTP_X_STUDIO_TIMESTAMP' => $ts, 'CONTENT_TYPE' => 'application/json'],
        $body
    )->assertStatus(202);

    expect(StudioFlowRun::where('flow_id', $flow->id)->exists())->toBeTrue();
});

// 2. HMAC invalid signature → 401, no run row
it('HMAC invalid signature → 401, no run row', function () {
    $flow = makeP8Flow(['slug' => 'smoke-hmac-invalid']);
    $ts = (string) time();

    $this->withHeaders(['X-Studio-Signature' => 'wrong', 'X-Studio-Timestamp' => $ts])
        ->postJson('/api/studio/webhooks/smoke-hmac-invalid')
        ->assertStatus(401);

    expect(StudioFlowRun::where('flow_id', $flow->id)->exists())->toBeFalse();
});

// 3. HMAC stale timestamp → 401, no run row
it('HMAC stale timestamp → 401, no run row', function () {
    $flow = makeP8Flow(['slug' => 'smoke-hmac-stale']);
    $staleTs = (string) (time() - 1000);
    $body = '{}';
    $staleSig = hash_hmac('sha256', $staleTs.'.'.$body, 'smoke-secret');

    $this->call('POST', '/api/studio/webhooks/smoke-hmac-stale', [], [], [],
        ['HTTP_X_STUDIO_SIGNATURE' => $staleSig, 'HTTP_X_STUDIO_TIMESTAMP' => $staleTs, 'CONTENT_TYPE' => 'application/json'],
        $body
    )->assertStatus(401);

    expect(StudioFlowRun::where('flow_id', $flow->id)->exists())->toBeFalse();
});

// 4. Rate limit: 4th req in a minute (limit=3) → 429 with Retry-After
it('rate limit: excess requests → 429 with Retry-After', function () {
    $flow = makeP8Flow(['slug' => 'smoke-rate', 'webhook_auth_mode' => WebhookAuthMode::None]);
    RateLimiter::clear('smoke-rate|127.0.0.1');

    for ($i = 0; $i < 3; $i++) {
        $this->postJson('/api/studio/webhooks/smoke-rate')->assertStatus(202);
    }

    $this->postJson('/api/studio/webhooks/smoke-rate')
        ->assertStatus(429)
        ->assertHeader('Retry-After');
});

// 5. Dangerous-op publish gate: editor blocked, admin allowed
it('dangerous-op gate: editor blocked, admin allowed', function () {
    $editor = $this->makeUserWith(['publish_flow']);
    $admin = $this->makeUserWith(['publish_flow', 'run_dangerous_operations']);

    $dangerousGraph = [
        'nodes' => [
            ['id' => 't', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
            ['id' => 'h', 'type' => 'operation', 'data' => ['operationType' => 'http_request', 'config' => ['url' => 'https://example.com', 'method' => 'GET']]],
        ],
        'edges' => [['source' => 't', 'target' => 'h']],
    ];

    $flow = StudioFlow::factory()->create(['draft_graph' => $dangerousGraph]);
    $service = app(PublishFlowVersion::class);

    $this->actingAs($editor);
    expect(fn () => $service->publish($flow))
        ->toThrow(CannotPublishDangerousFlowException::class);

    $flow->forceFill(['draft_graph' => $dangerousGraph])->save();
    $this->actingAs($admin);
    expect($service->publish($flow))->toBeInstanceOf(StudioFlowVersion::class);
});

// 6. Webhook-secret rotation: old secret rejected, new accepted, audit row written
it('secret rotation: old rejected, new accepted, audit written', function () {
    config()->set('filament-studio.flows.webhook_timestamp_window_seconds', 300);
    $flow = makeP8Flow(['slug' => 'smoke-rotate', 'webhook_secret' => 'old-secret-smoke']);
    $service = app(RotateWebhookSecret::class);
    $newSecret = $service->rotate($flow);
    $flow->refresh();

    // Old secret → invalid sig
    $ts = (string) time();
    $body = '{}';
    $verifier = app(HmacWebhookVerifier::class);
    $oldSig = hash_hmac('sha256', $ts.'.'.$body, 'old-secret-smoke');
    expect(fn () => $verifier->verify($body, $oldSig, $ts, (string) $flow->webhook_secret))
        ->toThrow(InvalidWebhookSignatureException::class);

    // New secret → valid
    $newSig = hash_hmac('sha256', $ts.'.'.$body, $newSecret);
    expect($verifier->verify($body, $newSig, $ts, $newSecret))->toBeTrue();

    // Audit row written
    expect(StudioFlowAuditLog::where('flow_id', $flow->id)->where('event', 'webhook_secret_rotated')->exists())->toBeTrue();
});

// 7. Audit log captures lifecycle: created, updated, deleted
it('audit log captures created, updated, deleted lifecycle', function () {
    $flow = StudioFlow::factory()->create();
    $flow->update(['description' => 'updated']);
    $flow->delete();

    $events = StudioFlowAuditLog::where('flow_id', $flow->id)
        ->orderBy('created_at')
        ->pluck('event')
        ->toArray();

    expect($events)->toContain('created')
        ->toContain('updated')
        ->toContain('deleted');
});

// 8. Sensitive-key masking strips api_key from step output
it('sensitive masking strips api_key from payload', function () {
    $masker = app(MasksSensitiveValues::class);
    $result = $masker->mask(['data' => ['api_key' => 'secret-abc', 'result' => 'ok']]);
    expect($result['data']['api_key'])->toBe('***');
    expect($result['data']['result'])->toBe('ok');
});

// 9. Per-flow redact paths strip a designated body field from stored trigger payload
it('per-flow redact paths strip designated field from stored trigger payload', function () {
    $flow = StudioFlow::factory()->create([
        'slug' => 'smoke-redact',
        'status' => FlowStatus::Active,
        'webhook_auth_mode' => WebhookAuthMode::None,
        'webhook_redact_paths' => ['/user/password'],
    ]);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => ['triggerType' => 'webhook', 'config' => []]]], 'edges' => []],
    ]);
    $flow->forceFill(['published_version_id' => $version->id])->save();

    $this->postJson('/api/studio/webhooks/smoke-redact', [
        'user' => ['password' => 'topsecret', 'name' => 'bob'],
    ])->assertStatus(202);

    $run = StudioFlowRun::where('flow_id', $flow->id)->latest('started_at')->first();
    expect($run->trigger_payload['body']['user']['password'] ?? null)->toBe('***');
    expect($run->trigger_payload['body']['user']['name'] ?? null)->toBe('bob');
});
