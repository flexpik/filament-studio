<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Services\SaveFlowDraft;

it('writes graph only to draft_graph and stamps draft_updated_at', function () {
    $flow = StudioFlow::factory()->withPublishedVersion(['nodes' => [['id' => 'old']], 'edges' => []])->create();
    $publishedVersionId = $flow->published_version_id;

    $graph = ['nodes' => [['id' => 'new']], 'edges' => []];
    app(SaveFlowDraft::class)->save($flow, $graph);

    $flow->refresh();
    expect($flow->draft_graph)->toBe($graph)
        ->and($flow->draft_updated_at)->not->toBeNull()
        ->and($flow->published_version_id)->toBe($publishedVersionId)
        ->and($flow->publishedVersion->graph)->toBe(['nodes' => [['id' => 'old']], 'edges' => []]);
});
