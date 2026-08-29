<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowAuditLog;

it('writes audit rows on created, updated and deleted', function () {
    $user = $this->makeUserWith(['view_flows', 'create_flows', 'update_flows', 'delete_flows']);
    $this->actingAs($user);

    $flow = StudioFlow::factory()->create();
    $flow->update(['description' => 'changed']);
    $flow->delete();

    $events = StudioFlowAuditLog::where('flow_id', $flow->id)
        ->orderBy('created_at')
        ->pluck('event')
        ->all();
    expect($events)->toBe(['created', 'updated', 'deleted']);

    $firstLog = StudioFlowAuditLog::where('flow_id', $flow->id)->first();
    expect($firstLog->actor_id)->toBe((string) $user->getKey());
    expect($firstLog->actor_type)->toBe('user');
});

it('uses system actor when no user is authenticated', function () {
    $flow = StudioFlow::factory()->create();

    $log = StudioFlowAuditLog::where('flow_id', $flow->id)->first();
    expect($log->actor_id)->toBeNull();
    expect($log->actor_type)->toBe('system');
});
