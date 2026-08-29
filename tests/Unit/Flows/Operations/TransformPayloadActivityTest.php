<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Operations\Data\TransformPayloadActivity;

it('returns the (already-resolved) config under the operation key', function () {
    $ctx = makeOperationContext(config: ['payload' => ['greeting' => 'hi Sera', 'count' => 3]]);

    $result = (new TransformPayloadActivity)->execute($ctx);

    expect($result->output())->toBe(['greeting' => 'hi Sera', 'count' => 3]);
});

it('returns empty array when payload key absent', function () {
    $ctx = makeOperationContext(config: []);

    $result = (new TransformPayloadActivity)->execute($ctx);

    expect($result->output())->toBe([]);
});
