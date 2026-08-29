<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;

it('casts status, payload, accountability, timestamps', function () {
    $run = StudioFlowRun::factory()->create([
        'status' => 'running',
        'trigger_payload' => ['key' => 'value'],
        'accountability' => ['user_id' => null, 'tenant_id' => 'a'],
    ]);
    $run = $run->fresh();
    expect($run->status)->toBe(FlowRunStatus::Running);
    expect($run->trigger_payload)->toBe(['key' => 'value']);
    expect($run->accountability)->toBe(['user_id' => null, 'tenant_id' => 'a']);
});

it('has flow and version relations', function () {
    $flow = StudioFlow::factory()->create();
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create(['flow_version_id' => $version->id]);
    expect($run->flow->is($flow))->toBeTrue();
    expect($run->flowVersion->is($version))->toBeTrue();
});

it('prunes runs older than configured retention', function () {
    config()->set('filament-studio.flows.log_retention_days', 7);
    $old = StudioFlowRun::factory()->create(['finished_at' => now()->subDays(10)]);
    $young = StudioFlowRun::factory()->create(['finished_at' => now()->subDays(3)]);

    (new StudioFlowRun)->prunable()->delete();

    expect(StudioFlowRun::find($old->id))->toBeNull();
    expect(StudioFlowRun::find($young->id))->not->toBeNull();
});
