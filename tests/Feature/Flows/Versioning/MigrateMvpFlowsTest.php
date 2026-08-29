<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Illuminate\Support\Facades\Artisan;

it('back-fills published_version_id from latest published version', function () {
    // Simulate "MVP" state: flow without pointer, but a published version exists.
    $flow = StudioFlow::factory()->create(['published_version_id' => null]);
    $v = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create(['version' => 1]);

    Artisan::call('studio:flows:backfill-versioning');

    expect($flow->fresh()->published_version_id)->toBe($v->id);
});

it('does not overwrite an existing published_version_id', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $originalId = $flow->published_version_id;

    Artisan::call('studio:flows:backfill-versioning');

    expect($flow->fresh()->published_version_id)->toBe($originalId);
});
