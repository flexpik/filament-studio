<?php

use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Livewire\FilterBuilder;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Livewire\Livewire;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => EavCast::Text,
        'is_filterable' => true,
    ]);

    StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'priority',
        'label' => 'Priority',
        'field_type' => 'integer',
        'eav_cast' => EavCast::Integer,
        'is_filterable' => true,
    ]);
});

it('mounts with an empty filter tree', function () {
    Livewire::test(FilterBuilder::class, ['collectionId' => $this->collection->id])
        ->assertSet('tree.logic', 'and')
        ->assertSet('tree.rules', []);
});

it('adds a rule to the root group', function () {
    Livewire::test(FilterBuilder::class, ['collectionId' => $this->collection->id])
        ->call('addRule')
        ->assertCount('tree.rules', 1);
});

it('removes a rule by path', function () {
    Livewire::test(FilterBuilder::class, ['collectionId' => $this->collection->id])
        ->call('addRule')
        ->call('addRule')
        ->assertCount('tree.rules', 2)
        ->call('removeRule', '0')
        ->assertCount('tree.rules', 1);
});

it('adds a nested group', function () {
    Livewire::test(FilterBuilder::class, ['collectionId' => $this->collection->id])
        ->call('addGroup')
        ->assertCount('tree.rules', 1)
        ->assertSet('tree.rules.0.logic', 'and')
        ->assertSet('tree.rules.0.rules', []);
});

it('toggles group logic between and/or', function () {
    Livewire::test(FilterBuilder::class, ['collectionId' => $this->collection->id])
        ->assertSet('tree.logic', 'and')
        ->call('toggleLogic', '')
        ->assertSet('tree.logic', 'or');
});

it('loads from a saved filter tree', function () {
    $tree = [
        'logic' => 'and',
        'rules' => [
            ['field' => 'title', 'operator' => 'contains', 'value' => 'hello'],
        ],
    ];

    Livewire::test(FilterBuilder::class, [
        'collectionId' => $this->collection->id,
        'initialTree' => $tree,
    ])
        ->assertSet('tree.logic', 'and')
        ->assertCount('tree.rules', 1)
        ->assertSet('tree.rules.0.field', 'title');
});

it('emits the filter tree when applied', function () {
    Livewire::test(FilterBuilder::class, ['collectionId' => $this->collection->id])
        ->call('addRule')
        ->set('tree.rules.0.field', 'title')
        ->set('tree.rules.0.operator', 'contains')
        ->set('tree.rules.0.value', 'hello')
        ->call('applyFilter')
        ->assertDispatched('filter-applied');
});

it('clears the filter tree', function () {
    Livewire::test(FilterBuilder::class, ['collectionId' => $this->collection->id])
        ->call('addRule')
        ->call('clearFilter')
        ->assertCount('tree.rules', 0)
        ->assertDispatched('filter-applied');
});
