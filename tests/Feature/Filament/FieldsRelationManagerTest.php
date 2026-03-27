<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioMigrationLog;
use Flexpik\FilamentStudio\Resources\CollectionManagerResource\Pages\EditCollection;
use Flexpik\FilamentStudio\Resources\CollectionManagerResource\RelationManagers\FieldsRelationManager;
use Illuminate\Foundation\Auth\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::forceCreate(['name' => 'Test', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password')]);
    actingAs($this->user);
    $this->collection = StudioCollection::factory()->create();
});

// --- Task 6: Table & Reorder ---

it('can render the relation manager', function () {
    Livewire::test(FieldsRelationManager::class, [
        'ownerRecord' => $this->collection,
        'pageClass' => EditCollection::class,
    ])
        ->assertSuccessful();
});

it('can list fields for a collection', function () {
    $fields = StudioField::factory()
        ->count(3)
        ->sequence(
            ['column_name' => 'title', 'sort_order' => 1],
            ['column_name' => 'body', 'sort_order' => 2],
            ['column_name' => 'status', 'sort_order' => 3],
        )
        ->create(['collection_id' => $this->collection->id]);

    Livewire::test(FieldsRelationManager::class, [
        'ownerRecord' => $this->collection,
        'pageClass' => EditCollection::class,
    ])
        ->assertCanSeeTableRecords($fields);
});

it('displays expected field columns', function () {
    StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_required' => true,
        'sort_order' => 0,
    ]);

    Livewire::test(FieldsRelationManager::class, [
        'ownerRecord' => $this->collection,
        'pageClass' => EditCollection::class,
    ])
        ->assertCanRenderTableColumn('column_name')
        ->assertCanRenderTableColumn('label')
        ->assertCanRenderTableColumn('field_type')
        ->assertCanRenderTableColumn('eav_cast')
        ->assertCanRenderTableColumn('is_required')
        ->assertCanRenderTableColumn('sort_order');
});

it('orders fields by sort_order', function () {
    $fieldB = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'b_field',
        'sort_order' => 2,
    ]);
    $fieldA = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'a_field',
        'sort_order' => 1,
    ]);

    Livewire::test(FieldsRelationManager::class, [
        'ownerRecord' => $this->collection,
        'pageClass' => EditCollection::class,
    ])
        ->assertCanSeeTableRecords([$fieldA, $fieldB], inOrder: true);
});

// --- Task 7: Create, Edit, Delete Actions ---

it('can create a field via the relation manager', function () {
    Livewire::test(FieldsRelationManager::class, [
        'ownerRecord' => $this->collection,
        'pageClass' => EditCollection::class,
    ])
        ->callTableAction('create', data: [
            'field_type' => 'text',
            'column_name' => 'title',
            'label' => 'Title',
            'eav_cast' => 'text',
            'is_required' => true,
            'width' => 'full',
        ])
        ->assertHasNoTableActionErrors();

    $field = StudioField::where('collection_id', $this->collection->id)
        ->where('column_name', 'title')
        ->first();

    expect($field)
        ->not->toBeNull()
        ->label->toBe('Title')
        ->field_type->toBe('text')
        ->is_required->toBeTrue();
});

it('logs add_field when creating a field', function () {
    Livewire::test(FieldsRelationManager::class, [
        'ownerRecord' => $this->collection,
        'pageClass' => EditCollection::class,
    ])
        ->callTableAction('create', data: [
            'field_type' => 'text',
            'column_name' => 'name',
            'label' => 'Name',
            'eav_cast' => 'text',
        ]);

    $log = StudioMigrationLog::where('collection_id', $this->collection->id)
        ->where('operation', 'add_field')
        ->first();

    expect($log)
        ->not->toBeNull()
        ->after_state->not->toBeNull();
});

it('can edit a field via the relation manager', function () {
    $field = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
    ]);

    Livewire::test(FieldsRelationManager::class, [
        'ownerRecord' => $this->collection,
        'pageClass' => EditCollection::class,
    ])
        ->callTableAction('edit', $field, data: [
            'label' => 'Updated Title',
        ])
        ->assertHasNoTableActionErrors();

    $field->refresh();
    expect($field->label)->toBe('Updated Title');
});

it('logs update_field when editing a field', function () {
    $field = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'title',
        'label' => 'Original',
        'field_type' => 'text',
    ]);

    Livewire::test(FieldsRelationManager::class, [
        'ownerRecord' => $this->collection,
        'pageClass' => EditCollection::class,
    ])
        ->callTableAction('edit', $field, data: [
            'label' => 'Changed',
        ]);

    $log = StudioMigrationLog::where('collection_id', $this->collection->id)
        ->where('operation', 'update_field')
        ->first();

    expect($log)
        ->not->toBeNull()
        ->before_state->not->toBeNull()
        ->after_state->not->toBeNull();
});

it('can delete a field via the relation manager', function () {
    $field = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'to_delete',
    ]);

    Livewire::test(FieldsRelationManager::class, [
        'ownerRecord' => $this->collection,
        'pageClass' => EditCollection::class,
    ])
        ->callTableAction('delete', $field);

    expect(StudioField::find($field->id))->toBeNull();
});

it('logs delete_field when deleting a field', function () {
    $field = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'to_delete',
    ]);

    Livewire::test(FieldsRelationManager::class, [
        'ownerRecord' => $this->collection,
        'pageClass' => EditCollection::class,
    ])
        ->callTableAction('delete', $field);

    $log = StudioMigrationLog::where('collection_id', $this->collection->id)
        ->where('operation', 'delete_field')
        ->first();

    expect($log)
        ->not->toBeNull()
        ->before_state->not->toBeNull();
});
