<?php

use Flexpik\FilamentStudio\Enums\FilterOperator;
use Flexpik\FilamentStudio\Filtering\FilterGroup;
use Flexpik\FilamentStudio\Filtering\FilterRule;

it('creates a filter rule from array', function () {
    $rule = FilterRule::fromArray([
        'field' => 'status',
        'operator' => 'eq',
        'value' => 'published',
    ]);

    expect($rule->field)->toBe('status');
    expect($rule->operator)->toBe(FilterOperator::Eq);
    expect($rule->value)->toBe('published');
});

it('creates a filter group from nested array', function () {
    $group = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'status', 'operator' => 'eq', 'value' => 'published'],
            ['field' => 'priority', 'operator' => 'gt', 'value' => 3],
        ],
    ]);

    expect($group->logic)->toBe('and');
    expect($group->children)->toHaveCount(2);
    expect($group->children[0])->toBeInstanceOf(FilterRule::class);
});

it('handles nested groups recursively', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'status', 'operator' => 'eq', 'value' => 'published'],
            [
                'logic' => 'or',
                'rules' => [
                    ['field' => 'category', 'operator' => 'eq', 'value' => 'news'],
                    ['field' => 'category', 'operator' => 'eq', 'value' => 'featured'],
                ],
            ],
        ],
    ]);

    expect($tree->children)->toHaveCount(2);
    expect($tree->children[1])->toBeInstanceOf(FilterGroup::class);
    expect($tree->children[1]->children)->toHaveCount(2);
});

it('serializes back to array', function () {
    $input = [
        'logic' => 'and',
        'rules' => [
            ['field' => 'status', 'operator' => 'eq', 'value' => 'published'],
            [
                'logic' => 'or',
                'rules' => [
                    ['field' => 'category', 'operator' => 'eq', 'value' => 'news'],
                    ['field' => 'category', 'operator' => 'eq', 'value' => 'featured'],
                ],
            ],
        ],
    ];

    $tree = FilterGroup::fromArray($input);
    $output = $tree->toArray();

    expect($output)->toBe($input);
});

it('creates an empty root group', function () {
    $group = FilterGroup::empty();

    expect($group->logic)->toBe('and');
    expect($group->children)->toBe([]);
    expect($group->isEmpty())->toBeTrue();
});

it('handles filter rule with dynamic variable value', function () {
    $rule = FilterRule::fromArray([
        'field' => 'created_by',
        'operator' => 'eq',
        'value' => '$CURRENT_USER',
    ]);

    expect($rule->value)->toBe('$CURRENT_USER');
    expect($rule->hasDynamicValue())->toBeTrue();
});

it('detects non-dynamic values', function () {
    $rule = FilterRule::fromArray([
        'field' => 'status',
        'operator' => 'eq',
        'value' => 'published',
    ]);

    expect($rule->hasDynamicValue())->toBeFalse();
});

it('hasDynamicValue with null value returns false', function () {
    $rule = new FilterRule(
        field: 'status',
        operator: FilterOperator::Eq,
        value: null,
    );

    expect($rule->hasDynamicValue())->toBeFalse();
});

it('hasDynamicValue with integer value returns false', function () {
    $rule = new FilterRule(
        field: 'priority',
        operator: FilterOperator::Gt,
        value: 5,
    );

    expect($rule->hasDynamicValue())->toBeFalse();
});

it('isRelational returns false when no related field', function () {
    $rule = new FilterRule(
        field: 'status',
        operator: FilterOperator::Eq,
        value: 'active',
    );

    expect($rule->isRelational())->toBeFalse();
});

it('isRelational returns true when relatedField is set', function () {
    $rule = new FilterRule(
        field: 'author',
        operator: FilterOperator::Eq,
        value: 'John',
        relatedField: 'name',
    );

    expect($rule->isRelational())->toBeTrue();
});

it('toArray includes related_field when set', function () {
    $rule = new FilterRule(
        field: 'author',
        operator: FilterOperator::Eq,
        value: 'John',
        relatedField: 'name',
    );

    $array = $rule->toArray();

    expect($array)->toHaveKey('related_field')
        ->and($array['related_field'])->toBe('name');
});

it('toArray excludes related_field when null', function () {
    $rule = new FilterRule(
        field: 'status',
        operator: FilterOperator::Eq,
        value: 'active',
    );

    $array = $rule->toArray();

    expect($array)->not->toHaveKey('related_field');
});
