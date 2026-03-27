<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Services\ConditionEvaluator;

beforeEach(function () {
    ConditionEvaluator::resetResolvers();
});

it('saves conditions to settings JSON when rules are added', function () {
    $collection = StudioCollection::factory()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'notes',
        'label' => 'Notes',
        'field_type' => 'textarea',
        'eav_cast' => 'text',
        'sort_order' => 1,
        'settings' => [
            'conditions' => [
                'visible' => [
                    'logic' => 'and',
                    'rules' => [
                        ['type' => 'field_value', 'field' => 'status', 'op' => 'equals', 'value' => 'active'],
                    ],
                ],
            ],
        ],
    ]);

    $field->refresh();
    $conditions = $field->settings['conditions'] ?? [];

    expect($conditions)->toHaveKey('visible');
    expect($conditions['visible']['rules'])->toHaveCount(1);
    expect($conditions['visible']['rules'][0]['type'])->toBe('field_value');
});

it('omits conditions key from settings when no rules exist', function () {
    $collection = StudioCollection::factory()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'notes',
        'label' => 'Notes',
        'field_type' => 'textarea',
        'eav_cast' => 'text',
        'sort_order' => 1,
        'settings' => [],
    ]);

    $conditions = $field->settings['conditions'] ?? null;

    expect($conditions)->toBeNull();
});

it('external resolver type is only available when resolvers are registered', function () {
    expect(ConditionEvaluator::getRegisteredResolverKeys())->toBe([]);

    ConditionEvaluator::registerResolver('feature_flag', fn () => true);

    expect(ConditionEvaluator::getRegisteredResolverKeys())->toBe(['feature_flag']);
});
