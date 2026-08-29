<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\GraphWalker;

it('resolves successors filtered by sourceHandle', function () {
    $graph = [
        'nodes' => [
            ['id' => 'cond', 'type' => 'operation'],
            ['id' => 'yes', 'type' => 'operation'],
            ['id' => 'no', 'type' => 'operation'],
        ],
        'edges' => [
            ['id' => 'e1', 'source' => 'cond', 'target' => 'yes', 'sourceHandle' => 'success'],
            ['id' => 'e2', 'source' => 'cond', 'target' => 'no', 'sourceHandle' => 'failure'],
        ],
    ];

    $walker = new GraphWalker;
    expect($walker->successors('cond', 'success', $graph))->toBe(['yes']);
    expect($walker->successors('cond', 'failure', $graph))->toBe(['no']);
    expect($walker->successors('cond', null, $graph))->toBe(['yes', 'no']);
});
