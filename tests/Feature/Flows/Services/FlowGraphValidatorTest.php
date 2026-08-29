<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Exceptions\InvalidFlowGraphException;
use Flexpik\FilamentStudio\Flows\Services\FlowGraphValidator;

it('throws InvalidFlowGraphException for unknown operation key', function () {
    $validator = app(FlowGraphValidator::class);

    $graph = ['nodes' => [
        ['id' => 'trigger_1', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
        ['id' => 'node_bad', 'type' => 'operation', 'data' => ['operationType' => 'not_yet_installed_op', 'config' => []]],
    ], 'edges' => []];

    $validator->validate($graph);
})->throws(InvalidFlowGraphException::class);

it('passes validation for a valid graph with known ops', function () {
    $validator = app(FlowGraphValidator::class);

    $graph = ['nodes' => [
        ['id' => 'trigger_1', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
        ['id' => 'node_log', 'type' => 'operation', 'data' => ['operationType' => 'log_message', 'config' => ['level' => 'info', 'message' => 'hello']]],
    ], 'edges' => []];

    $validator->validate($graph);

    expect(true)->toBeTrue();
});

it('throws InvalidFlowGraphException for invalid config', function () {
    $validator = app(FlowGraphValidator::class);

    $graph = ['nodes' => [
        ['id' => 'trigger_1', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
        ['id' => 'node_log', 'type' => 'operation', 'data' => ['operationType' => 'log_message', 'config' => ['level' => 'invalid_level']]],
    ], 'edges' => []];

    $validator->validate($graph);
})->throws(InvalidFlowGraphException::class);

it('throws InvalidFlowGraphException for unknown trigger key', function () {
    $validator = app(FlowGraphValidator::class);

    $graph = ['nodes' => [
        ['id' => 'trigger_1', 'type' => 'trigger', 'data' => ['triggerType' => 'ghost_trigger']],
    ], 'edges' => []];

    $validator->validate($graph);
})->throws(InvalidFlowGraphException::class);

it('throws when operationType is missing from operation node', function () {
    $validator = app(FlowGraphValidator::class);

    $graph = ['nodes' => [
        ['id' => 'node_1', 'type' => 'operation', 'data' => ['config' => []]],
    ], 'edges' => []];

    $validator->validate($graph);
})->throws(InvalidFlowGraphException::class);

it('collects multiple errors across nodes', function () {
    $validator = app(FlowGraphValidator::class);

    $graph = ['nodes' => [
        ['id' => 'node_bad1', 'type' => 'operation', 'data' => ['operationType' => 'ghost_op_1', 'config' => []]],
        ['id' => 'node_bad2', 'type' => 'operation', 'data' => ['operationType' => 'ghost_op_2', 'config' => []]],
    ], 'edges' => []];

    try {
        $validator->validate($graph);
        expect(false)->toBeTrue('Should have thrown');
    } catch (InvalidFlowGraphException $e) {
        expect($e->errors())->toHaveCount(2);
        expect($e->errors())->toHaveKeys(['node_bad1', 'node_bad2']);
    }
});

it('passes validation for a graph with only a trigger node', function () {
    $validator = app(FlowGraphValidator::class);

    $graph = ['nodes' => [
        ['id' => 'trigger_1', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
    ], 'edges' => []];

    $validator->validate($graph);

    expect(true)->toBeTrue();
});

it('passes validation for empty graph', function () {
    $validator = app(FlowGraphValidator::class);

    $validator->validate(['nodes' => [], 'edges' => []]);

    expect(true)->toBeTrue();
});
