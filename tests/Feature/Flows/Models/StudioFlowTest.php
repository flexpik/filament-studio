<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Enums\LoggingMode;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;

it('creates a flow with cast attributes', function () {
    $flow = StudioFlow::factory()->create([
        'status' => 'active',
        'logging_mode' => 'errors_only',
        'webhook_secret' => 'sup3r-secret',
    ]);

    expect($flow->status)->toBe(FlowStatus::Active);
    expect($flow->logging_mode)->toBe(LoggingMode::ErrorsOnly);
    expect($flow->webhook_secret)->toBe('sup3r-secret'); // decrypted on access
    expect($flow->id)->toBeString()->toHaveLength(36);
});

it('uses the configured table prefix', function () {
    expect((new StudioFlow)->getTable())->toBe('studio_flows');
});

it('encrypts webhook_secret at rest', function () {
    $flow = StudioFlow::factory()->create(['webhook_secret' => 'plain']);
    $raw = DB::table('studio_flows')->where('id', $flow->id)->value('webhook_secret');
    expect($raw)->not->toBe('plain');
});
