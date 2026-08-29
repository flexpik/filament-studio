<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Triggers\WebhookTrigger;

it('register persists webhook_secret on the flow when auth_mode=hmac and secret missing', function () {
    $flow = StudioFlow::factory()->create(['webhook_secret' => null]);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => [
                'triggerType' => 'webhook',
                'config' => ['auth_mode' => 'hmac', 'allowed_methods' => ['POST'], 'response_mode' => 'async'],
            ]]],
            'edges' => [],
        ],
    ]);

    (new WebhookTrigger)->register($version);

    expect($flow->fresh()->webhook_secret)->toBeString()->not->toBeEmpty();
});

it('does not overwrite an existing webhook_secret', function () {
    $flow = StudioFlow::factory()->create(['webhook_secret' => 'keepme']);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'webhook', 'config' => ['auth_mode' => 'hmac']]]], 'edges' => []],
    ]);

    (new WebhookTrigger)->register($version);

    expect($flow->fresh()->webhook_secret)->toBe('keepme');
});

it('unregister clears webhook_secret', function () {
    $flow = StudioFlow::factory()->create(['webhook_secret' => 'something']);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create();

    (new WebhookTrigger)->unregister($version);

    expect($flow->fresh()->webhook_secret)->toBeNull();
});
