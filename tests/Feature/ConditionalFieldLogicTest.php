<?php

use Filament\Schemas\Components\Section;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Services\ConditionEvaluator;
use Flexpik\FilamentStudio\Services\DynamicFormSchemaBuilder;

beforeEach(function () {
    ConditionEvaluator::resetResolvers();
});

it('builds form schema with visible conditions applied', function () {
    $collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'delivery_method',
        'label' => 'Delivery Method',
        'field_type' => 'select',
        'eav_cast' => 'text',
        'sort_order' => 0,
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'shipping_address',
        'label' => 'Shipping Address',
        'field_type' => 'textarea',
        'eav_cast' => 'text',
        'sort_order' => 1,
        'settings' => [
            'conditions' => [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'delivery_method', 'op' => 'equals', 'value' => 'ship'],
                    ],
                ],
            ],
        ],
    ]);

    $schema = DynamicFormSchemaBuilder::build($collection, 'create');

    expect($schema)->not->toBeEmpty();
    expect($schema[0])->toBeInstanceOf(Section::class);
});

it('marks trigger fields as live and leaves non-trigger fields non-live', function () {
    $collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'status',
        'label' => 'Status',
        'field_type' => 'select',
        'eav_cast' => 'text',
        'sort_order' => 0,
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'unrelated',
        'label' => 'Unrelated',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 1,
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'notes',
        'label' => 'Notes',
        'field_type' => 'textarea',
        'eav_cast' => 'text',
        'sort_order' => 2,
        'settings' => [
            'conditions' => [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'equals', 'value' => 'published'],
                    ],
                ],
            ],
        ],
    ]);

    $triggerFields = ConditionEvaluator::collectTriggerFields(
        $collection->fields()->ordered()->get()
    );

    expect($triggerFields)->toContain('status');
    expect($triggerFields)->not->toContain('unrelated');
    expect($triggerFields)->not->toContain('notes');
});

it('evaluates record_state conditions differently for create vs edit', function () {
    $evaluator = new ConditionEvaluator(
        conditions: [
            'visible' => [
                'logic' => 'and',
                'rules' => [
                    ['type' => 'record_state', 'state' => 'edit'],
                ],
            ],
        ],
        pageContext: 'create',
    );

    $get = fn (string $key) => null;
    expect($evaluator->buildVisibleClosure()($get))->toBeFalse();

    $evaluator = new ConditionEvaluator(
        conditions: [
            'visible' => [
                'logic' => 'and',
                'rules' => [
                    ['type' => 'record_state', 'state' => 'edit'],
                ],
            ],
        ],
        pageContext: 'edit',
    );

    expect($evaluator->buildVisibleClosure()($get))->toBeTrue();
});

it('treats null pageContext as permissive for record_state rules', function () {
    $evaluator = new ConditionEvaluator(
        conditions: [
            'visible' => [
                'logic' => 'and',
                'rules' => [
                    ['type' => 'record_state', 'state' => 'edit'],
                ],
            ],
        ],
        pageContext: null,
    );

    $get = fn (string $key) => null;
    expect($evaluator->buildVisibleClosure()($get))->toBeTrue();
});

it('detects circular dependencies', function () {
    $collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'a',
        'label' => 'Field A',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 0,
        'settings' => [
            'conditions' => [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'b', 'op' => 'equals', 'value' => 'x'],
                    ],
                ],
            ],
        ],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'b',
        'label' => 'Field B',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 1,
        'settings' => [
            'conditions' => [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'a', 'op' => 'equals', 'value' => 'y'],
                    ],
                ],
            ],
        ],
    ]);

    $cycle = ConditionEvaluator::detectCycles(
        $collection->fields()->ordered()->get()
    );

    expect($cycle)->not->toBeNull();
    expect($cycle)->toContain('a');
    expect($cycle)->toContain('b');
});

it('evaluates disabled condition closure', function () {
    $evaluator = new ConditionEvaluator(
        conditions: [
            'disabled' => [
                'logic' => 'and',
                'rules' => [
                    ['type' => 'record_state', 'state' => 'edit'],
                ],
            ],
        ],
        pageContext: 'edit',
    );

    expect($evaluator->hasDisabled())->toBeTrue();

    $get = fn (string $key) => null;
    expect($evaluator->buildDisabledClosure()($get))->toBeTrue();
});

it('registers and uses external resolvers', function () {
    ConditionEvaluator::registerResolver('always_true', fn () => true);
    ConditionEvaluator::registerResolver('always_false', fn () => false);

    $evaluatorTrue = new ConditionEvaluator(
        conditions: [
            'visible' => [
                'logic' => 'and',
                'rules' => [
                    ['type' => 'external', 'resolver' => 'always_true'],
                ],
            ],
        ],
        pageContext: 'create',
    );

    $evaluatorFalse = new ConditionEvaluator(
        conditions: [
            'visible' => [
                'logic' => 'and',
                'rules' => [
                    ['type' => 'external', 'resolver' => 'always_false'],
                ],
            ],
        ],
        pageContext: 'create',
    );

    $get = fn (string $key) => null;
    expect($evaluatorTrue->buildVisibleClosure()($get))->toBeTrue();
    expect($evaluatorFalse->buildVisibleClosure()($get))->toBeFalse();
});
