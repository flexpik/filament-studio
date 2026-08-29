<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;

it('withPublishedVersion state mints and links a v1 version', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    expect($flow->published_version_id)->not->toBeNull()
        ->and($flow->publishedVersion?->version)->toBe(1)
        ->and($flow->publishedVersion?->published_at)->not->toBeNull();
});

it('published() state on version sets published_by', function () {
    $v = StudioFlowVersion::factory()->published('tester')->create();
    expect($v->published_at)->not->toBeNull()
        ->and($v->published_by)->toBe('tester');
});
