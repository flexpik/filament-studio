<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowSecret;

it('encrypts the value at rest and decrypts on access', function () {
    $secret = StudioFlowSecret::factory()->create(['key' => 'api_token', 'value' => 'plain']);
    expect($secret->fresh()->value)->toBe('plain');
    $raw = DB::table('studio_flow_secrets')->where('id', $secret->id)->value('value');
    expect($raw)->not->toBe('plain');
});

it('belongs to a flow', function () {
    $flow = StudioFlow::factory()->create();
    $secret = StudioFlowSecret::factory()->for($flow, 'flow')->create();
    expect($secret->flow->is($flow))->toBeTrue();
});
