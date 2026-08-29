<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;
use Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException;
use Flexpik\FilamentStudio\Flows\Operations\Logic\ConditionActivity;
use Flexpik\FilamentStudio\Flows\Operations\Logic\ConditionConfig;

it('condition returns branch success when filter matches', function () {
    $ctx = makeOperationContext(
        trigger: ['status' => 'active'],
        config: ['filter' => ['logic' => 'and', 'rules' => [
            ['path' => '$trigger.status', 'operator' => 'equals', 'value' => 'active'],
        ]]],
    );

    $op = new ConditionActivity;
    $result = $op->execute($ctx);

    expect($result)->toBeInstanceOf(OperationResult::class);
    expect($result->isSuccess())->toBeTrue();
    expect($result->branch())->toBe('success');
});

it('condition returns branch failure when filter does not match', function () {
    $ctx = makeOperationContext(
        trigger: ['status' => 'inactive'],
        config: ['filter' => ['logic' => 'and', 'rules' => [
            ['path' => '$trigger.status', 'operator' => 'equals', 'value' => 'active'],
        ]]],
    );

    $op = new ConditionActivity;
    $result = $op->execute($ctx);

    expect($result->branch())->toBe('failure');
});

it('ConditionConfig has schema, defaults, and validate', function () {
    $cfg = new ConditionConfig;
    expect($cfg->schema())->toBeArray();
    expect($cfg->defaults())->toHaveKey('filter');
    $cfg->validate(['filter' => ['logic' => 'and', 'rules' => []]]);
    expect(true)->toBeTrue();
});

it('ConditionConfig validate throws for invalid logic', function () {
    $cfg = new ConditionConfig;
    $cfg->validate(['filter' => ['logic' => 'bad_logic', 'rules' => []]]);
})->throws(InvalidOperationConfigException::class);
