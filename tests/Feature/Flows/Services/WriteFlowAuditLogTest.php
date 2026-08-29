<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowAuditLog;
use Flexpik\FilamentStudio\Flows\Services\WriteFlowAuditLog;
use Illuminate\Foundation\Auth\User;

it('writes an audit log row with correct shape', function () {
    $flow = StudioFlow::factory()->create();
    $metadata = ['version' => 3, 'change_summary' => 'add HTTP node'];

    $service = app(WriteFlowAuditLog::class);
    $log = $service->write($flow, 'published', $metadata);

    expect($log)->toBeInstanceOf(StudioFlowAuditLog::class);
    expect($log->event)->toBe('published');
    expect($log->metadata)->toBe($metadata);
    expect($log->actor_id)->toBeNull();
    expect($log->actor_type)->toBe('system');
    expect(StudioFlowAuditLog::where('event', 'published')->count())->toBe(1);
});

it('records actor when provided', function () {
    $flow = StudioFlow::factory()->create();

    $actor = User::forceCreate([
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $service = app(WriteFlowAuditLog::class);
    $log = $service->write($flow, 'webhook_secret_rotated', ['previous_secret_prefix' => 'abcd'], $actor);

    expect($log->actor_id)->toBe((string) $actor->getKey());
    expect($log->actor_type)->toBe('user');
    expect($log->metadata['previous_secret_prefix'])->toBe('abcd');
});
