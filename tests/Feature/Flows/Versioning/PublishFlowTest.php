<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;

it('mints a new version, points published_version_id at it, clears draft', function () {
    $flow = StudioFlow::factory()->create([
        'draft_graph' => ['nodes' => [['id' => 't1', 'type' => 'trigger']], 'edges' => []],
        'draft_updated_at' => now(),
    ]);

    $service = app(PublishFlowVersion::class);
    $v = $service->publish($flow, changeSummary: 'first publish', publishedBy: 'user-1');

    $flow->refresh();
    expect($v->version)->toBe(1)
        ->and($v->published_at)->not->toBeNull()
        ->and($v->published_by)->toBe('user-1')
        ->and($v->change_summary)->toBe('first publish')
        ->and($flow->published_version_id)->toBe($v->id)
        ->and($flow->draft_graph)->toBeNull()
        ->and($flow->draft_updated_at)->toBeNull();
});

it('increments version number on each publish', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $flow->update(['draft_graph' => ['nodes' => [], 'edges' => []]]);

    $v2 = app(PublishFlowVersion::class)->publish($flow);
    expect($v2->version)->toBe(2);
});

it('refuses to publish when draft_graph is null', function () {
    $flow = StudioFlow::factory()->create(['draft_graph' => null]);

    expect(fn () => app(PublishFlowVersion::class)->publish($flow))
        ->toThrow(RuntimeException::class, 'no_draft_to_publish');
});
