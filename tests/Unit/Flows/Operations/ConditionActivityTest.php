<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Operations\Logic\ConditionActivity;

it('returns result=true when filter group matches the data chain', function () {
    $ctx = makeOperationContext(
        trigger: ['email' => 'hi@example.com'],
        config: [
            'filter' => [
                'logic' => 'and',
                'rules' => [
                    ['path' => '$trigger.email', 'operator' => 'equals', 'value' => 'hi@example.com'],
                ],
            ],
        ],
    );

    $result = (new ConditionActivity)->execute($ctx);

    expect($result->branch())->toBe('success');
    expect($result->output())->toBe(['result' => true]);
});

it('returns result=false when no match', function () {
    $ctx = makeOperationContext(
        trigger: ['email' => 'a@b.com'],
        config: [
            'filter' => [
                'logic' => 'and',
                'rules' => [
                    ['path' => '$trigger.email', 'operator' => 'equals', 'value' => 'wrong@x.com'],
                ],
            ],
        ],
    );

    $result = (new ConditionActivity)->execute($ctx);

    expect($result->branch())->toBe('failure');
    expect($result->output())->toBe(['result' => false]);
});

it('handles nested OR groups', function () {
    $ctx = makeOperationContext(
        trigger: ['type' => 'priority'],
        config: [
            'filter' => [
                'logic' => 'or',
                'rules' => [
                    ['path' => '$trigger.type', 'operator' => 'equals', 'value' => 'priority'],
                    ['path' => '$trigger.type', 'operator' => 'equals', 'value' => 'urgent'],
                ],
            ],
        ],
    );

    $result = (new ConditionActivity)->execute($ctx);

    expect($result->branch())->toBe('success');
    expect($result->output())->toBe(['result' => true]);
});
