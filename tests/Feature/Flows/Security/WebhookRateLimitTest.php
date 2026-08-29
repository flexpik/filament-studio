<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Enums\WebhookAuthMode;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    Bus::fake();
    Cache::flush();
    config()->set('filament-studio.flows.webhook_rate_limit_per_minute', 3);
});

it('returns 429 after exceeding the rate limit', function () {
    $flow = StudioFlow::factory()->create([
        'slug' => 'rate-test',
        'status' => FlowStatus::Active,
        'webhook_auth_mode' => WebhookAuthMode::None,
    ]);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => ['triggerType' => 'webhook', 'config' => []]]], 'edges' => []],
    ]);
    $flow->forceFill(['published_version_id' => $version->id])->save();

    // 3 allowed requests
    for ($i = 0; $i < 3; $i++) {
        $this->postJson('/api/studio/webhooks/rate-test')->assertStatus(202);
    }

    // 4th should be rate limited
    $response = $this->postJson('/api/studio/webhooks/rate-test');
    $response->assertStatus(429);
    $response->assertHeader('Retry-After');
});
