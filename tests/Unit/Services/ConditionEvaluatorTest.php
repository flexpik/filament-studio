<?php

use Flexpik\FilamentStudio\Services\ConditionEvaluator;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    ConditionEvaluator::resetResolvers();
});

describe('resolver registry', function () {
    it('registers and retrieves resolver keys', function () {
        ConditionEvaluator::registerResolver('feature_flag', fn () => true);
        ConditionEvaluator::registerResolver('subscription', fn () => false);

        expect(ConditionEvaluator::getRegisteredResolverKeys())
            ->toBe(['feature_flag', 'subscription']);
    });

    it('rejects invalid resolver keys', function () {
        ConditionEvaluator::registerResolver('invalid-key', fn () => true);
    })->throws(InvalidArgumentException::class);

    it('rejects resolver keys with special characters', function () {
        ConditionEvaluator::registerResolver('key:with:colons', fn () => true);
    })->throws(InvalidArgumentException::class);

    it('resolves a non-reactive resolver and caches result', function () {
        $callCount = 0;
        ConditionEvaluator::registerResolver('counter', function () use (&$callCount) {
            $callCount++;

            return true;
        });

        $result1 = ConditionEvaluator::resolve('counter', [], null);
        $result2 = ConditionEvaluator::resolve('counter', [], null);

        expect($result1)->toBeTrue();
        expect($result2)->toBeTrue();
        expect($callCount)->toBe(1); // cached
    });

    it('resolves a reactive resolver without caching', function () {
        $callCount = 0;
        ConditionEvaluator::registerResolver('reactive_counter', function () use (&$callCount) {
            $callCount++;

            return true;
        }, reactive: true);

        ConditionEvaluator::resolve('reactive_counter', [], null);
        ConditionEvaluator::resolve('reactive_counter', [], null);

        expect($callCount)->toBe(2); // not cached
    });

    it('returns true for unregistered resolver (permissive fallback)', function () {
        $result = ConditionEvaluator::resolve('nonexistent', [], null);

        expect($result)->toBeTrue();
    });

    it('resets all resolvers and cache', function () {
        ConditionEvaluator::registerResolver('test', fn () => true);
        ConditionEvaluator::resolve('test', [], null);

        ConditionEvaluator::resetResolvers();

        expect(ConditionEvaluator::getRegisteredResolverKeys())->toBe([]);
    });
});

describe('rule evaluation', function () {
    it('evaluates field_value equals operator', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'equals', 'value' => 'active'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        expect($evaluator->hasVisible())->toBeTrue();

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => $key === 'status' ? 'active' : null;
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => $key === 'status' ? 'inactive' : null;
        expect($closure($get))->toBeFalse();
    });

    it('evaluates field_value not_equals operator', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'not_equals', 'value' => 'draft'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 'published';
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => 'draft';
        expect($closure($get))->toBeFalse();
    });

    it('evaluates field_value in operator', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'in', 'value' => ['active', 'published']],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 'active';
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => 'draft';
        expect($closure($get))->toBeFalse();
    });

    it('evaluates field_value not_in operator', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'not_in', 'value' => ['archived', 'deleted']],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 'active';
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => 'archived';
        expect($closure($get))->toBeFalse();
    });

    it('evaluates field_value is_empty operator', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'notes', 'op' => 'is_empty'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => null;
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => '';
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => 'some text';
        expect($closure($get))->toBeFalse();
    });

    it('evaluates field_value is_not_empty operator', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'notes', 'op' => 'is_not_empty'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 'has content';
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => null;
        expect($closure($get))->toBeFalse();
    });

    it('evaluates field_value greater_than operator', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'quantity', 'op' => 'greater_than', 'value' => 10],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 15;
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => 5;
        expect($closure($get))->toBeFalse();
    });

    it('evaluates field_value less_than operator', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'quantity', 'op' => 'less_than', 'value' => 10],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 5;
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => 15;
        expect($closure($get))->toBeFalse();
    });

    it('evaluates field_value contains operator for text', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'title', 'op' => 'contains', 'value' => 'urgent'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 'This is urgent!';
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => 'Nothing special';
        expect($closure($get))->toBeFalse();
    });

    it('evaluates field_value contains operator for arrays', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'tags', 'op' => 'contains', 'value' => 'featured'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => ['featured', 'trending'];
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => ['normal', 'boring'];
        expect($closure($get))->toBeFalse();
    });
});

describe('logic combinators', function () {
    it('combines rules with AND logic', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'equals', 'value' => 'active'],
                        ['type' => 'field_value', 'field' => 'priority', 'op' => 'equals', 'value' => 'high'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => match ($key) {
            'status' => 'active',
            'priority' => 'high',
            default => null,
        };
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => match ($key) {
            'status' => 'active',
            'priority' => 'low',
            default => null,
        };
        expect($closure($get))->toBeFalse();
    });

    it('combines rules with OR logic', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'or',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'equals', 'value' => 'active'],
                        ['type' => 'field_value', 'field' => 'priority', 'op' => 'equals', 'value' => 'high'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => match ($key) {
            'status' => 'inactive',
            'priority' => 'high',
            default => null,
        };
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => match ($key) {
            'status' => 'inactive',
            'priority' => 'low',
            default => null,
        };
        expect($closure($get))->toBeFalse();
    });

    it('defaults logic to AND when not specified', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'a', 'op' => 'equals', 'value' => '1'],
                        ['type' => 'field_value', 'field' => 'b', 'op' => 'equals', 'value' => '2'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => match ($key) {
            'a' => '1',
            'b' => 'wrong',
            default => null,
        };
        expect($closure($get))->toBeFalse();
    });
});

describe('record_state rules', function () {
    it('matches create page context', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'record_state', 'state' => 'create'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;
        expect($closure($get))->toBeTrue();
    });

    it('does not match wrong page context', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'record_state', 'state' => 'create'],
                    ],
                ],
            ],
            pageContext: 'edit',
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;
        expect($closure($get))->toBeFalse();
    });
});

describe('permission rules', function () {
    it('bakes permission result as static boolean', function () {
        $user = new User;

        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'permission', 'gate' => 'nonexistent_gate'],
                    ],
                ],
            ],
            pageContext: 'create',
            user: $user,
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;
        expect($closure($get))->toBeFalse();
    });

    it('negates permission result when negate is true', function () {
        $user = new User;

        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'permission', 'gate' => 'nonexistent_gate', 'negate' => true],
                    ],
                ],
            ],
            pageContext: 'create',
            user: $user,
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;
        expect($closure($get))->toBeTrue();
    });
});

describe('has* methods', function () {
    it('returns false when no conditions exist for target', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [],
            pageContext: 'create',
        );

        expect($evaluator->hasVisible())->toBeFalse();
        expect($evaluator->hasRequired())->toBeFalse();
        expect($evaluator->hasDisabled())->toBeFalse();
    });

    it('returns false when target has empty rules', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => ['logic' => 'and', 'rules' => []],
            ],
            pageContext: 'create',
        );

        expect($evaluator->hasVisible())->toBeFalse();
    });

    it('returns true when target has rules', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'required' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'record_state', 'state' => 'edit'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        expect($evaluator->hasRequired())->toBeTrue();
    });
});

describe('input validation', function () {
    it('handles malformed conditions gracefully', function () {
        $evaluator = new ConditionEvaluator(
            conditions: ['visible' => 'not_an_array'],
            pageContext: 'create',
        );

        expect($evaluator->hasVisible())->toBeFalse();
    });

    it('skips rules with missing type', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['field' => 'status', 'op' => 'equals', 'value' => 'active'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;
        expect($closure($get))->toBeTrue();
    });

    it('skips unknown rule types', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'unknown_type', 'field' => 'status'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;
        expect($closure($get))->toBeTrue();
    });
});

describe('collectTriggerFields', function () {
    it('returns column names referenced in field_value rules', function () {
        $fields = collect([
            makeFieldWithConditions('address', [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'delivery_method', 'op' => 'equals', 'value' => 'ship'],
                    ],
                ],
            ]),
            makeFieldWithConditions('notes', [
                'required' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'equals', 'value' => 'published'],
                        ['type' => 'record_state', 'state' => 'edit'],
                    ],
                ],
            ]),
            makeFieldWithConditions('normal_field', null),
        ]);

        $triggerFields = ConditionEvaluator::collectTriggerFields($fields);

        expect($triggerFields)->toBe(['delivery_method', 'status']);
    });

    it('deduplicates trigger fields', function () {
        $fields = collect([
            makeFieldWithConditions('a', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'status', 'op' => 'equals', 'value' => 'x'],
                ]],
            ]),
            makeFieldWithConditions('b', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'status', 'op' => 'equals', 'value' => 'y'],
                ]],
            ]),
        ]);

        $triggerFields = ConditionEvaluator::collectTriggerFields($fields);

        expect($triggerFields)->toBe(['status']);
    });
});

describe('detectCycles', function () {
    it('returns null when no cycles exist', function () {
        $fields = collect([
            makeFieldWithConditions('b', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'a', 'op' => 'equals', 'value' => 'x'],
                ]],
            ]),
            makeFieldWithConditions('a', null),
        ]);

        expect(ConditionEvaluator::detectCycles($fields))->toBeNull();
    });

    it('detects simple two-node cycle', function () {
        $fields = collect([
            makeFieldWithConditions('a', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'b', 'op' => 'equals', 'value' => 'x'],
                ]],
            ]),
            makeFieldWithConditions('b', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'a', 'op' => 'equals', 'value' => 'y'],
                ]],
            ]),
        ]);

        $cycle = ConditionEvaluator::detectCycles($fields);

        expect($cycle)->not->toBeNull();
        expect($cycle)->toContain('a');
        expect($cycle)->toContain('b');
    });
});

describe('boundary: is_empty and is_not_empty with falsy values', function () {
    it('treats integer 0 as not empty', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'count', 'op' => 'is_empty'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 0;
        expect($closure($get))->toBeFalse();
    });

    it('treats boolean false as not empty', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'flag', 'op' => 'is_empty'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => false;
        expect($closure($get))->toBeFalse();
    });

    it('is_not_empty returns true for integer 0', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'count', 'op' => 'is_not_empty'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 0;
        expect($closure($get))->toBeTrue();
    });

    it('is_not_empty returns true for boolean false', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'flag', 'op' => 'is_not_empty'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => false;
        expect($closure($get))->toBeTrue();
    });
});

describe('boundary: greater_than and less_than edge cases', function () {
    it('greater_than returns false for non-numeric field value', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'quantity', 'op' => 'greater_than', 'value' => 10],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 'not_a_number';
        expect($closure($get))->toBeFalse();
    });

    it('less_than returns false for non-numeric compare value', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'quantity', 'op' => 'less_than', 'value' => 'abc'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 5;
        expect($closure($get))->toBeFalse();
    });

    it('greater_than returns false at exact boundary', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'quantity', 'op' => 'greater_than', 'value' => 10],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 10;
        expect($closure($get))->toBeFalse();
    });

    it('less_than returns false at exact boundary', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'quantity', 'op' => 'less_than', 'value' => 10],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 10;
        expect($closure($get))->toBeFalse();
    });
});

describe('boundary: contains edge cases', function () {
    it('returns false when field value is neither string nor array', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'data', 'op' => 'contains', 'value' => 'test'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 12345;
        expect($closure($get))->toBeFalse();
    });

    it('returns false when compare value is not string and field is string', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'title', 'op' => 'contains', 'value' => 123],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 'some title with 123';
        expect($closure($get))->toBeFalse();
    });
});

describe('boundary: in and not_in with non-array compare value', function () {
    it('in returns false when compare value is not array', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'in', 'value' => 'active'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 'active';
        expect($closure($get))->toBeFalse();
    });

    it('not_in returns false when compare value is not array', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'not_in', 'value' => 'archived'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 'active';
        expect($closure($get))->toBeFalse();
    });
});

describe('boundary: record_state with null page context', function () {
    it('returns true when page context is null', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'record_state', 'state' => 'create'],
                    ],
                ],
            ],
            pageContext: null,
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;
        expect($closure($get))->toBeTrue();
    });
});

describe('boundary: permission with null user', function () {
    it('returns false when user is null', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'permission', 'gate' => 'some_gate'],
                    ],
                ],
            ],
            pageContext: 'create',
            user: null,
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;
        expect($closure($get))->toBeFalse();
    });
});

describe('boundary: buildRequiredClosure and buildDisabledClosure', function () {
    it('buildRequiredClosure evaluates required conditions', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'required' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'equals', 'value' => 'published'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildRequiredClosure();

        $get = fn (string $key) => 'published';
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => 'draft';
        expect($closure($get))->toBeFalse();
    });

    it('buildDisabledClosure evaluates disabled conditions', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'disabled' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'locked', 'op' => 'equals', 'value' => 'yes'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildDisabledClosure();

        $get = fn (string $key) => 'yes';
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => 'no';
        expect($closure($get))->toBeFalse();
    });
});

describe('boundary: buildDehydratedClosure uses same logic as visible', function () {
    it('buildDehydratedClosure delegates to visible target', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'equals', 'value' => 'active'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildDehydratedClosure();

        $get = fn (string $key) => 'active';
        expect($closure($get))->toBeTrue();

        $get = fn (string $key) => 'inactive';
        expect($closure($get))->toBeFalse();
    });
});

describe('boundary: unknown field_value operator', function () {
    it('returns true for unknown operator', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'starts_with', 'value' => 'act'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        $get = fn (string $key) => 'active';
        expect($closure($get))->toBeTrue();
    });
});

describe('boundary: external resolver rules', function () {
    it('evaluates reactive external resolver within closure', function () {
        ConditionEvaluator::registerResolver('feature_active', fn () => true, reactive: true);

        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'external', 'resolver' => 'feature_active', 'reactive' => true],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;
        expect($closure($get))->toBeTrue();
    });

    it('evaluates non-reactive external resolver as static', function () {
        ConditionEvaluator::registerResolver('feature_disabled', fn () => false);

        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'external', 'resolver' => 'feature_disabled', 'reactive' => false],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;
        expect($closure($get))->toBeFalse();
    });
});

describe('boundary: all unknown rule types filtered out', function () {
    it('hasVisible returns false when all rules are unknown types', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'unknown_alpha'],
                        ['type' => 'unknown_beta'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        expect($evaluator->hasVisible())->toBeFalse();
    });
});

describe('boundary: collectTriggerFields edge cases', function () {
    it('returns empty array for empty fields collection', function () {
        $fields = collect([]);

        $triggerFields = ConditionEvaluator::collectTriggerFields($fields);

        expect($triggerFields)->toBe([]);
    });

    it('ignores non-field_value rules', function () {
        $fields = collect([
            makeFieldWithConditions('a', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'record_state', 'state' => 'create'],
                    ['type' => 'permission', 'gate' => 'edit'],
                ]],
            ]),
        ]);

        $triggerFields = ConditionEvaluator::collectTriggerFields($fields);

        expect($triggerFields)->toBe([]);
    });

    it('collects from all target types: visible, required, disabled', function () {
        $fields = collect([
            makeFieldWithConditions('a', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'f1', 'op' => 'equals', 'value' => 'x'],
                ]],
                'required' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'f2', 'op' => 'equals', 'value' => 'y'],
                ]],
                'disabled' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'f3', 'op' => 'equals', 'value' => 'z'],
                ]],
            ]),
        ]);

        $triggerFields = ConditionEvaluator::collectTriggerFields($fields);

        expect($triggerFields)->toBe(['f1', 'f2', 'f3']);
    });

    it('handles non-array rules gracefully', function () {
        $fields = collect([
            (object) [
                'column_name' => 'broken',
                'settings' => [
                    'conditions' => [
                        'visible' => ['logic' => 'and', 'rules' => 'not_an_array'],
                    ],
                ],
            ],
        ]);

        $triggerFields = ConditionEvaluator::collectTriggerFields($fields);

        expect($triggerFields)->toBe([]);
    });
});

describe('boundary: detectCycles edge cases', function () {
    it('returns null for empty fields collection', function () {
        $fields = collect([]);

        expect(ConditionEvaluator::detectCycles($fields))->toBeNull();
    });

    it('returns null for linear chain without cycle', function () {
        $fields = collect([
            makeFieldWithConditions('a', null),
            makeFieldWithConditions('b', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'a', 'op' => 'equals', 'value' => 'x'],
                ]],
            ]),
            makeFieldWithConditions('c', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'b', 'op' => 'equals', 'value' => 'y'],
                ]],
            ]),
        ]);

        expect(ConditionEvaluator::detectCycles($fields))->toBeNull();
    });

    it('detects self-reference cycle', function () {
        $fields = collect([
            makeFieldWithConditions('a', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'a', 'op' => 'equals', 'value' => 'x'],
                ]],
            ]),
        ]);

        $cycle = ConditionEvaluator::detectCycles($fields);

        expect($cycle)->not->toBeNull();
        expect($cycle)->toContain('a');
    });

    it('detects three-node cycle', function () {
        $fields = collect([
            makeFieldWithConditions('a', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'b', 'op' => 'equals', 'value' => 'x'],
                ]],
            ]),
            makeFieldWithConditions('b', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'c', 'op' => 'equals', 'value' => 'y'],
                ]],
            ]),
            makeFieldWithConditions('c', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'a', 'op' => 'equals', 'value' => 'z'],
                ]],
            ]),
        ]);

        $cycle = ConditionEvaluator::detectCycles($fields);

        expect($cycle)->not->toBeNull();
        expect($cycle)->toContain('a');
        expect($cycle)->toContain('b');
        expect($cycle)->toContain('c');
    });

    it('ignores non-field_value rules in dependency graph', function () {
        $fields = collect([
            makeFieldWithConditions('a', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'record_state', 'state' => 'create'],
                ]],
            ]),
            makeFieldWithConditions('b', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'permission', 'gate' => 'edit'],
                ]],
            ]),
        ]);

        expect(ConditionEvaluator::detectCycles($fields))->toBeNull();
    });
});

describe('mutation: hasVisible/hasRequired/hasDisabled boundary with exactly 1 rule (lines 34,39,44)', function () {
    // Lines 34,39,44: GreaterToGreaterOrEqual / DecrementInteger
    // count($rules) > 0 mutated to count($rules) >= 0 would make empty rules return true
    // count($rules) > -1 would also make empty rules return true
    // We need: exactly 0 rules => false, exactly 1 rule => true
    it('hasVisible returns false with zero valid rules after filtering', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => ['logic' => 'and', 'rules' => []],
            ],
            pageContext: 'create',
        );

        expect($evaluator->hasVisible())->toBeFalse();
    });

    it('hasRequired returns false with zero valid rules after filtering', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'required' => ['logic' => 'and', 'rules' => []],
            ],
            pageContext: 'create',
        );

        expect($evaluator->hasRequired())->toBeFalse();
    });

    it('hasDisabled returns false with zero valid rules after filtering', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'disabled' => ['logic' => 'and', 'rules' => []],
            ],
            pageContext: 'create',
        );

        expect($evaluator->hasDisabled())->toBeFalse();
    });

    it('hasVisible returns true with exactly one valid rule', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'x', 'op' => 'equals', 'value' => '1'],
                ]],
            ],
            pageContext: 'create',
        );

        expect($evaluator->hasVisible())->toBeTrue();
    });

    it('hasRequired returns true with exactly one valid rule', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'required' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'x', 'op' => 'equals', 'value' => '1'],
                ]],
            ],
            pageContext: 'create',
        );

        expect($evaluator->hasRequired())->toBeTrue();
    });

    it('hasDisabled returns true with exactly one valid rule', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'disabled' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'x', 'op' => 'equals', 'value' => '1'],
                ]],
            ],
            pageContext: 'create',
        );

        expect($evaluator->hasDisabled())->toBeTrue();
    });
});

describe('mutation: logWarning removal on unregistered resolver (line 81)', function () {
    // Line 81: RemoveMethodCall - if logWarning is removed, behavior still returns true
    // We test that Log::warning is actually called
    it('logs a warning when resolving an unregistered resolver', function () {
        $spy = Log::spy();

        $result = ConditionEvaluator::resolve('not_registered_key', [], null);

        expect($result)->toBeTrue();

        $spy->shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message) {
                return str_contains($message, 'not_registered_key') && str_contains($message, 'not registered');
            });
    });
});

describe('mutation: cache key concatenation (line 90)', function () {
    // Line 90: CoalesceRemoveLeft ($user?->getKey() ?? 'guest' => 'guest')
    //          ConcatRemoveRight ($key.'_'.(...) => $key)
    //          ConcatSwitchSides
    // Different users should get different cache entries
    it('caches separately for different users vs guest', function () {
        $callCount = 0;
        ConditionEvaluator::registerResolver('user_check', function ($state, $user) use (&$callCount) {
            $callCount++;

            return $user !== null;
        });

        // Resolve as guest
        $guestResult = ConditionEvaluator::resolve('user_check', [], null);
        expect($guestResult)->toBeFalse();
        expect($callCount)->toBe(1);

        // Resolve as a user with key=1 - should NOT use guest cache
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getKey')->andReturn(1);

        $userResult = ConditionEvaluator::resolve('user_check', [], $user);
        expect($userResult)->toBeTrue();
        expect($callCount)->toBe(2); // called again, different cache key
    });

    it('uses same cache for same user key', function () {
        $callCount = 0;
        ConditionEvaluator::registerResolver('same_user', function () use (&$callCount) {
            $callCount++;

            return true;
        });

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getKey')->andReturn(42);

        ConditionEvaluator::resolve('same_user', [], $user);
        ConditionEvaluator::resolve('same_user', [], $user);

        expect($callCount)->toBe(1);
    });
});

describe('mutation: boolean cast in resolve (lines 96, 102)', function () {
    // Lines 96, 102: RemoveBooleanCast - if (bool) is removed, truthy/falsy values pass through
    // A resolver returning a truthy non-boolean (like 1 or "yes") must still return bool
    it('casts non-reactive resolver result to boolean', function () {
        ConditionEvaluator::registerResolver('truthy_int', fn () => 1);

        $result = ConditionEvaluator::resolve('truthy_int', [], null);

        expect($result)->toBeTrue();
        expect($result)->toBeBool();
    });

    it('casts reactive resolver result to boolean', function () {
        ConditionEvaluator::registerResolver('truthy_string', fn () => 'yes', reactive: true);

        $result = ConditionEvaluator::resolve('truthy_string', [], null);

        expect($result)->toBeTrue();
        expect($result)->toBeBool();
    });

    it('casts non-reactive falsy result to false boolean', function () {
        ConditionEvaluator::registerResolver('falsy_int', fn () => 0);

        $result = ConditionEvaluator::resolve('falsy_int', [], null);

        expect($result)->toBeFalse();
        expect($result)->toBeBool();
    });

    it('casts reactive falsy result to false boolean', function () {
        ConditionEvaluator::registerResolver('falsy_empty', fn () => '', reactive: true);

        $result = ConditionEvaluator::resolve('falsy_empty', [], null);

        expect($result)->toBeFalse();
        expect($result)->toBeBool();
    });
});

describe('mutation: collectTriggerFields continue/break and logic (lines 133,137,144,164,168)', function () {
    // Lines 133,164: ContinueToBreak - if continue becomes break, loop stops early
    // We need multiple targets where some have non-array rules and others have field_value rules AFTER
    it('continues past non-array rules to find field_value in later targets', function () {
        $fields = collect([
            (object) [
                'column_name' => 'field_a',
                'settings' => [
                    'conditions' => [
                        'visible' => ['logic' => 'and', 'rules' => 'not_an_array'],
                        'required' => ['logic' => 'and', 'rules' => [
                            ['type' => 'field_value', 'field' => 'trigger1', 'op' => 'equals', 'value' => 'x'],
                        ]],
                        'disabled' => ['logic' => 'and', 'rules' => [
                            ['type' => 'field_value', 'field' => 'trigger2', 'op' => 'equals', 'value' => 'y'],
                        ]],
                    ],
                ],
            ],
        ]);

        $triggerFields = ConditionEvaluator::collectTriggerFields($fields);

        expect($triggerFields)->toContain('trigger1');
        expect($triggerFields)->toContain('trigger2');
        expect($triggerFields)->toHaveCount(2);
    });

    // Lines 137,168: BooleanAndToBooleanOr, EmptyStringToNotEmpty
    // ($rule['type'] ?? '') === 'field_value' && isset($rule['field'])
    // If AND becomes OR, rules without field would be included
    // If '' becomes non-empty, default for missing type would change
    it('skips rules with type field_value but missing field key', function () {
        $fields = collect([
            makeFieldWithConditions('a', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'op' => 'equals', 'value' => 'x'], // missing 'field'
                ]],
            ]),
        ]);

        $triggerFields = ConditionEvaluator::collectTriggerFields($fields);

        expect($triggerFields)->toBe([]);
    });

    it('skips rules with no type key in collectTriggerFields', function () {
        $fields = collect([
            makeFieldWithConditions('a', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['field' => 'some_field', 'op' => 'equals', 'value' => 'x'], // missing 'type'
                ]],
            ]),
        ]);

        $triggerFields = ConditionEvaluator::collectTriggerFields($fields);

        expect($triggerFields)->toBe([]);
    });

    // Line 144: UnwrapArrayValues - array_values(array_unique($triggerFields))
    // Without array_values, keys are preserved from array_unique, resulting in non-sequential keys
    it('returns sequential zero-indexed array after deduplication', function () {
        $fields = collect([
            makeFieldWithConditions('a', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'dup', 'op' => 'equals', 'value' => 'x'],
                ]],
            ]),
            makeFieldWithConditions('b', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'unique_one', 'op' => 'equals', 'value' => 'y'],
                ]],
            ]),
            makeFieldWithConditions('c', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'dup', 'op' => 'equals', 'value' => 'z'],
                ]],
            ]),
        ]);

        $triggerFields = ConditionEvaluator::collectTriggerFields($fields);

        // Without array_values, the keys would be [0, 1, 2] where index 2 is removed by unique
        // and you'd get [0 => 'dup', 1 => 'unique_one'] with non-sequential keys
        expect($triggerFields)->toBe(['dup', 'unique_one']);
        expect(array_keys($triggerFields))->toBe([0, 1]);
    });
});

describe('mutation: detectCycles array operations (lines 160,174,175,200,204,207,208,218,225,226)', function () {
    // Line 160: RemoveArrayItem - removing a target from ['visible', 'required', 'disabled']
    // Test that deps from all three targets are used
    it('detects cycle through disabled conditions', function () {
        $fields = collect([
            makeFieldWithConditions('a', [
                'disabled' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'b', 'op' => 'equals', 'value' => 'x'],
                ]],
            ]),
            makeFieldWithConditions('b', [
                'disabled' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'a', 'op' => 'equals', 'value' => 'y'],
                ]],
            ]),
        ]);

        $cycle = ConditionEvaluator::detectCycles($fields);

        expect($cycle)->not->toBeNull();
    });

    it('detects cycle through required conditions', function () {
        $fields = collect([
            makeFieldWithConditions('a', [
                'required' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'b', 'op' => 'equals', 'value' => 'x'],
                ]],
            ]),
            makeFieldWithConditions('b', [
                'required' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'a', 'op' => 'equals', 'value' => 'y'],
                ]],
            ]),
        ]);

        $cycle = ConditionEvaluator::detectCycles($fields);

        expect($cycle)->not->toBeNull();
    });

    // Line 174: GreaterToGreaterOrEqual / DecrementInteger - count($deps) > 0
    // If mutated to >= 0, fields with no deps get added to graph (with empty array)
    // This alone won't cause a false cycle but tests the boundary
    it('does not add fields with zero dependencies to graph', function () {
        $fields = collect([
            makeFieldWithConditions('a', null), // no conditions
            makeFieldWithConditions('b', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'a', 'op' => 'equals', 'value' => 'x'],
                ]],
            ]),
        ]);

        // b depends on a, a has no deps - no cycle
        expect(ConditionEvaluator::detectCycles($fields))->toBeNull();
    });

    // Line 175: UnwrapArrayUnique - without array_unique, duplicate deps remain
    it('deduplicates dependencies in cycle detection', function () {
        $fields = collect([
            makeFieldWithConditions('a', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'b', 'op' => 'equals', 'value' => 'x'],
                ]],
                'required' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'b', 'op' => 'equals', 'value' => 'y'],
                ]],
            ]),
            makeFieldWithConditions('b', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'a', 'op' => 'equals', 'value' => 'z'],
                ]],
            ]),
        ]);

        $cycle = ConditionEvaluator::detectCycles($fields);

        // Even with duplicates, cycle is detected. The important thing is array_unique
        // prevents the same dep from appearing multiple times in the graph
        expect($cycle)->not->toBeNull();
        expect($cycle)->toContain('a');
        expect($cycle)->toContain('b');
    });

    // Line 200: UnwrapArrayMerge, UnwrapArraySlice, RemoveArrayItem
    // array_merge(array_slice($path, $cycleStart), [$node])
    // The cycle path must start from the cycle start node and end with it repeated
    it('returns correct cycle path starting and ending with the cycle node', function () {
        $fields = collect([
            makeFieldWithConditions('a', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'b', 'op' => 'equals', 'value' => 'x'],
                ]],
            ]),
            makeFieldWithConditions('b', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'a', 'op' => 'equals', 'value' => 'y'],
                ]],
            ]),
        ]);

        $cycle = ConditionEvaluator::detectCycles($fields);

        expect($cycle)->not->toBeNull();
        // Cycle should be like ['a', 'b', 'a'] - first and last should match
        expect($cycle[0])->toBe($cycle[count($cycle) - 1]);
    });

    // Line 204: RemoveEarlyReturn in DFS - if removed, visited nodes would be re-processed
    it('does not re-visit already visited nodes (early return for visited)', function () {
        // A diamond: a->b, a->c, b->d, c->d — no cycle, d is visited twice via b and c
        $fields = collect([
            makeFieldWithConditions('a', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'b', 'op' => 'equals', 'value' => 'x'],
                ]],
                'required' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'c', 'op' => 'equals', 'value' => 'y'],
                ]],
            ]),
            makeFieldWithConditions('b', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'd', 'op' => 'equals', 'value' => 'z'],
                ]],
            ]),
            makeFieldWithConditions('c', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'd', 'op' => 'equals', 'value' => 'w'],
                ]],
            ]),
            makeFieldWithConditions('d', null),
        ]);

        // No cycle, just a diamond shape
        expect(ConditionEvaluator::detectCycles($fields))->toBeNull();
    });

    // Lines 207,208: TrueToFalse - $visited[$node] = true; $inStack[$node] = true;
    // If set to false, isset() would still return true but the values would be wrong
    // The flags are checked via isset(), so true->false wouldn't change isset() behavior
    // BUT it would affect the logic if values were checked directly
    // These are covered by the cycle detection tests already - the key test is that
    // a non-cyclic diamond doesn't produce false positive
    it('correctly tracks visited and inStack for complex graph', function () {
        // Chain a->b->c with separate d->b (d references b which is already visited)
        $fields = collect([
            makeFieldWithConditions('a', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'b', 'op' => 'equals', 'value' => 'x'],
                ]],
            ]),
            makeFieldWithConditions('b', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'c', 'op' => 'equals', 'value' => 'y'],
                ]],
            ]),
            makeFieldWithConditions('c', null),
            makeFieldWithConditions('d', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'b', 'op' => 'equals', 'value' => 'z'],
                ]],
            ]),
        ]);

        // No cycle even though d->b and a->b->c
        expect(ConditionEvaluator::detectCycles($fields))->toBeNull();
    });

    // Line 218: ArrayPopToArrayShift - array_pop vs array_shift changes which end is removed
    // After DFS, the path should be properly restored (pop removes last, shift removes first)
    it('properly backtracks path during DFS (array_pop correctness)', function () {
        // a->b, a->c->d->a would be a cycle via c branch
        // But if array_shift is used instead of array_pop, the path gets corrupted
        $fields = collect([
            makeFieldWithConditions('x', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'y', 'op' => 'equals', 'value' => '1'],
                ]],
                'required' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'z', 'op' => 'equals', 'value' => '2'],
                ]],
            ]),
            makeFieldWithConditions('y', null),
            makeFieldWithConditions('z', [
                'visible' => ['logic' => 'and', 'rules' => [
                    ['type' => 'field_value', 'field' => 'x', 'op' => 'equals', 'value' => '3'],
                ]],
            ]),
        ]);

        $cycle = ConditionEvaluator::detectCycles($fields);

        expect($cycle)->not->toBeNull();
        // The cycle should contain x and z
        expect($cycle)->toContain('x');
        expect($cycle)->toContain('z');
        // First and last should be same (cycle)
        expect($cycle[0])->toBe($cycle[count($cycle) - 1]);
    });

    // Line 225: IfNegated - if (Facade::getFacadeApplication()) negated
    // Line 226: RemoveMethodCall - Log::warning removal
    // These are in logWarning - tested via the resolver warning test above

    // Lines 133,164: ContinueToBreak in detectCycles - non-array rules should be skipped
    it('continues past non-array rules in detectCycles', function () {
        $fields = collect([
            (object) [
                'column_name' => 'a',
                'settings' => [
                    'conditions' => [
                        'visible' => ['logic' => 'and', 'rules' => 'not_array'],
                        'required' => ['logic' => 'and', 'rules' => [
                            ['type' => 'field_value', 'field' => 'b', 'op' => 'equals', 'value' => 'x'],
                        ]],
                    ],
                ],
            ],
            (object) [
                'column_name' => 'b',
                'settings' => [
                    'conditions' => [
                        'visible' => ['logic' => 'and', 'rules' => 'not_array'],
                        'disabled' => ['logic' => 'and', 'rules' => [
                            ['type' => 'field_value', 'field' => 'a', 'op' => 'equals', 'value' => 'y'],
                        ]],
                    ],
                ],
            ],
        ]);

        $cycle = ConditionEvaluator::detectCycles($fields);

        // Should detect cycle a->b->a through required and disabled targets
        expect($cycle)->not->toBeNull();
    });
});

describe('mutation: buildClosureForTarget (lines 312,316,317)', function () {
    // Lines 312,317: TrueToFalse - external_reactive resolver returning true in closure
    // Line 316: DecrementInteger - count($results) === 0, if decremented to -1, empty results won't return true
    it('returns true when closure has no resolved rules (empty results)', function () {
        // This happens when the target has no parsed conditions
        $evaluator = new ConditionEvaluator(
            conditions: [],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;

        // Default closure should return true
        expect($closure($get))->toBeTrue();
    });

    it('external_reactive resolver returning false makes closure false', function () {
        ConditionEvaluator::registerResolver('check_false', fn () => false, reactive: true);

        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'external', 'resolver' => 'check_false', 'reactive' => true],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;

        expect($closure($get))->toBeFalse();
    });
});

describe('mutation: evaluateFieldValueRule empty string defaults (lines 328,365,386,393)', function () {
    // Line 328: EmptyStringToNotEmpty - $get($rule['field'] ?? '')
    // If default becomes non-empty, missing field key would get a non-empty default
    it('uses empty string default when field key is missing from rule', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'op' => 'equals', 'value' => 'test'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        // When field key is missing, it defaults to '' - $get('') should be called
        $calledWith = null;
        $get = function (string $key) use (&$calledWith) {
            $calledWith = $key;

            return null;
        };

        $closure($get);

        expect($calledWith)->toBe('');
    });

    // Line 365: EmptyStringToNotEmpty - $rule['gate'] ?? ''
    it('uses empty string default for missing gate in permission rule', function () {
        $user = new User;

        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'permission'], // no gate key
                    ],
                ],
            ],
            pageContext: 'create',
            user: $user,
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;

        // Should not throw, falls back to empty string gate
        expect($closure($get))->toBeFalse();
    });

    // Line 386: EmptyStringToNotEmpty - $rule['state'] ?? ''
    it('uses empty string default for missing state in record_state rule', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'record_state'], // no state key
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;

        // pageContext is 'create', state defaults to '' — not equal
        expect($closure($get))->toBeFalse();
    });

    // Line 393: EmptyStringToNotEmpty - $rule['resolver'] ?? ''
    it('uses empty string default for missing resolver in external rule', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'external'], // no resolver key
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;

        // Should fall back to permissive (unregistered resolver returns true)
        expect($closure($get))->toBeTrue();
    });
});

describe('mutation: equals/not_equals loose comparison (lines 333,334)', function () {
    // Lines 333,334: EqualToIdentical, NotEqualToNotIdentical
    // == vs === and != vs !== matter with different types
    it('equals uses loose comparison (int vs string)', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'count', 'op' => 'equals', 'value' => '1'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        // int 1 == string '1' is true with loose comparison
        $get = fn (string $key) => 1;
        expect($closure($get))->toBeTrue();
    });

    it('not_equals uses loose comparison (int vs string)', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'count', 'op' => 'not_equals', 'value' => '1'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        // int 1 != string '1' is false with loose comparison (they are equal)
        $get = fn (string $key) => 1;
        expect($closure($get))->toBeFalse();
    });
});

describe('mutation: prepareExternalRule (lines 394,396)', function () {
    // Line 394: CoalesceRemoveLeft ($rule['reactive'] ?? false => false)
    //           FalseToTrue ($rule['reactive'] ?? false => $rule['reactive'] ?? true)
    // Line 396: IfNegated - if ($isReactive) negated
    it('non-reactive external rule is resolved at build time (static)', function () {
        $callCount = 0;
        ConditionEvaluator::registerResolver('static_check', function () use (&$callCount) {
            $callCount++;

            return true;
        });

        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'external', 'resolver' => 'static_check', 'reactive' => false],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        expect($callCount)->toBe(1); // resolved during buildClosureForTarget

        $get = fn (string $key) => null;
        $closure($get);
        $closure($get);

        // Should not have been called again (it's static/baked)
        expect($callCount)->toBe(1);
    });

    it('reactive external rule is resolved at evaluation time', function () {
        $callCount = 0;
        ConditionEvaluator::registerResolver('reactive_check', function () use (&$callCount) {
            $callCount++;

            return true;
        }, reactive: true);

        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'external', 'resolver' => 'reactive_check', 'reactive' => true],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        expect($callCount)->toBe(0); // NOT resolved during build

        $get = fn (string $key) => null;
        $closure($get);

        expect($callCount)->toBe(1); // resolved during closure call
    });

    it('missing reactive key defaults to non-reactive (static)', function () {
        $callCount = 0;
        ConditionEvaluator::registerResolver('default_check', function () use (&$callCount) {
            $callCount++;

            return true;
        });

        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'external', 'resolver' => 'default_check'],
                        // no 'reactive' key - should default to false
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();

        // Should have been resolved during buildClosureForTarget (non-reactive)
        expect($callCount)->toBe(1);
    });
});

describe('mutation: parseConditions validRules count (line 269)', function () {
    // Line 269: GreaterToGreaterOrEqual / DecrementInteger - count($validRules) > 0
    it('does not include target with zero valid rules after filtering', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'invalid_type_1'],
                        ['type' => 'invalid_type_2'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        // All rules are invalid, so parsedConditions should not contain 'visible'
        expect($evaluator->hasVisible())->toBeFalse();
    });

    it('includes target when at least one valid rule exists among invalid ones', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'invalid_type'],
                        ['type' => 'field_value', 'field' => 'x', 'op' => 'equals', 'value' => '1'],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        expect($evaluator->hasVisible())->toBeTrue();
    });
});

describe('mutation: detectCycles early return and boolean flags (lines 204,207,208,362)', function () {
    // Line 362: RemoveEarlyReturn in evaluatePermissionRule - if (!$this->user) return false
    it('returns false for permission rule when user is null (early return)', function () {
        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'or',
                    'rules' => [
                        ['type' => 'permission', 'gate' => 'any_gate'],
                    ],
                ],
            ],
            pageContext: 'create',
            user: null,
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => null;

        expect($closure($get))->toBeFalse();
    });
});

describe('mutation: logWarning facade check (line 225)', function () {
    // Line 225: IfNegated - if (Facade::getFacadeApplication()) negated
    // If negated, logging would happen when app is NOT set
    // Line 226: RemoveMethodCall - Log::warning removal
    it('calls Log::warning when facade application exists', function () {
        $spy = Log::spy();

        // Trigger a malformed rule warning via parseConditions
        new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['no_type_key' => true], // missing 'type' triggers logWarning
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $spy->shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message) {
                return str_contains($message, 'skipping rule with missing type');
            });
    });
});

describe('mutation: OR logic with mixed results in closure (line 312 external_reactive)', function () {
    it('OR logic returns true when external_reactive is true and field_value is false', function () {
        ConditionEvaluator::registerResolver('or_check', fn () => true, reactive: true);

        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'or',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'equals', 'value' => 'active'],
                        ['type' => 'external', 'resolver' => 'or_check', 'reactive' => true],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => 'inactive'; // field_value rule is false

        expect($closure($get))->toBeTrue(); // but external is true, OR => true
    });

    it('AND logic returns false when external_reactive is false and field_value is true', function () {
        ConditionEvaluator::registerResolver('and_fail', fn () => false, reactive: true);

        $evaluator = new ConditionEvaluator(
            conditions: [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'equals', 'value' => 'active'],
                        ['type' => 'external', 'resolver' => 'and_fail', 'reactive' => true],
                    ],
                ],
            ],
            pageContext: 'create',
        );

        $closure = $evaluator->buildVisibleClosure();
        $get = fn (string $key) => 'active'; // field_value rule is true

        expect($closure($get))->toBeFalse(); // but external is false, AND => false
    });
});

/**
 * Helper: create a mock StudioField-like object with settings.conditions.
 */
function makeFieldWithConditions(string $columnName, ?array $conditions): object
{
    return (object) [
        'column_name' => $columnName,
        'settings' => $conditions ? ['conditions' => $conditions] : [],
    ];
}
