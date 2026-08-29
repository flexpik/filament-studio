<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowAuditLog;

it('persists an audit-log row with json metadata', function () {
    $log = StudioFlowAuditLog::factory()->create([
        'event' => 'published',
        'metadata' => ['version' => 3, 'change_summary' => 'add HTTP node'],
    ]);

    expect($log->metadata)->toBe(['version' => 3, 'change_summary' => 'add HTTP node']);
    expect($log->flow)->toBeInstanceOf(StudioFlow::class);
});

it('has no updated_at column', function () {
    $log = StudioFlowAuditLog::factory()->create();
    expect($log->updated_at)->toBeNull();
});
