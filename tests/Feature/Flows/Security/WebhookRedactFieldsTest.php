<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Enums\WebhookAuthMode;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    Bus::fake();
});

it('redacts a designated body field from the stored trigger payload', function () {
    $flow = StudioFlow::factory()->create([
        'slug' => 'redact-test',
        'status' => FlowStatus::Active,
        'webhook_auth_mode' => WebhookAuthMode::None,
        'webhook_redact_paths' => ['/user/password'],
    ]);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => ['triggerType' => 'webhook', 'config' => []]]], 'edges' => []],
    ]);
    $flow->forceFill(['published_version_id' => $version->id])->save();

    $this->postJson('/api/studio/webhooks/redact-test', [
        'user' => ['password' => 'secret123', 'name' => 'alice'],
    ])->assertStatus(202);

    $run = StudioFlowRun::query()->where('flow_id', $flow->id)->latest()->first();
    expect($run)->not->toBeNull();
    expect($run->trigger_payload['body']['user']['password'] ?? null)->toBe('***');
    expect($run->trigger_payload['body']['user']['name'] ?? null)->toBe('alice');
});

it('leaves payload untouched when redact_paths is empty', function () {
    $flow = StudioFlow::factory()->create([
        'slug' => 'no-redact',
        'status' => FlowStatus::Active,
        'webhook_auth_mode' => WebhookAuthMode::None,
        'webhook_redact_paths' => [],
    ]);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => ['triggerType' => 'webhook', 'config' => []]]], 'edges' => []],
    ]);
    $flow->forceFill(['published_version_id' => $version->id])->save();

    $this->postJson('/api/studio/webhooks/no-redact', [
        'user' => ['name' => 'alice'],
    ])->assertStatus(202);

    $run = StudioFlowRun::query()->where('flow_id', $flow->id)->latest()->first();
    expect($run->trigger_payload['body']['user']['name'] ?? null)->toBe('alice');
});
