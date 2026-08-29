<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Services\RollbackFlowVersion;

it('creates v(N+1) with same graph as the target version and updates pointer', function () {
    $flow = StudioFlow::factory()->create();
    $v1 = StudioFlowVersion::factory()->for($flow, 'flow')->published('user-a')
        ->create(['version' => 1, 'graph' => ['nodes' => [['id' => 'a']], 'edges' => []]]);
    $v2 = StudioFlowVersion::factory()->for($flow, 'flow')->published('user-b')
        ->create(['version' => 2, 'graph' => ['nodes' => [['id' => 'b']], 'edges' => []]]);
    $flow->update(['published_version_id' => $v2->id]);

    $restored = app(RollbackFlowVersion::class)->rollback($flow, $v1, publishedBy: 'user-c');

    expect($restored->version)->toBe(3)
        ->and($restored->graph)->toBe($v1->graph)
        ->and($restored->change_summary)->toBe('Restored from v1')
        ->and($flow->fresh()->published_version_id)->toBe($restored->id);
});

it('refuses to roll back to a version of a different flow', function () {
    $a = StudioFlow::factory()->create();
    $b = StudioFlow::factory()->create();
    $otherVersion = StudioFlowVersion::factory()->for($b, 'flow')->published()->create(['version' => 1]);

    expect(fn () => app(RollbackFlowVersion::class)->rollback($a, $otherVersion))
        ->toThrow(RuntimeException::class, 'version_belongs_to_other_flow');
});
