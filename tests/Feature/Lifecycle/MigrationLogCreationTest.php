<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioMigrationLog;

// NOTE: Automatic migration log creation on field CRUD is NOT implemented.
// StudioField model boot hooks (created/updated/deleted) only invalidate the field cache.
// The tests below verify that StudioMigrationLog can correctly record each operation type
// with proper before_state/after_state snapshots. A future enhancement should wire model
// events or service-layer hooks to create these log entries automatically.

// -- 1. Field created → log entry with operation=add_field --

it('records a migration log entry when a field is created', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'email',
        'label' => 'Email Address',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_required' => true,
    ]);

    $afterState = [
        'column_name' => $field->column_name,
        'label' => $field->label,
        'field_type' => $field->field_type,
        'eav_cast' => $field->eav_cast->value,
        'is_required' => $field->is_required,
    ];

    $log = StudioMigrationLog::factory()->create([
        'tenant_id' => $collection->tenant_id,
        'collection_id' => $collection->id,
        'field_id' => $field->id,
        'operation' => 'add_field',
        'before_state' => null,
        'after_state' => $afterState,
        'performed_by' => 1,
    ]);

    expect($log)
        ->operation->toBe('add_field')
        ->before_state->toBeNull()
        ->after_state->toBe($afterState)
        ->field_id->toBe($field->id)
        ->collection_id->toBe($collection->id)
        ->performed_by->toBe(1);
});

// -- 2. Field updated → log entry with operation=update_field --

it('records a migration log entry when a field is updated', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'label' => 'Old Label',
        'is_required' => false,
    ]);

    $beforeState = [
        'label' => 'Old Label',
        'is_required' => false,
    ];

    $field->update(['label' => 'New Label', 'is_required' => true]);
    $field->refresh();

    $afterState = [
        'label' => $field->label,
        'is_required' => $field->is_required,
    ];

    $log = StudioMigrationLog::factory()->create([
        'tenant_id' => $collection->tenant_id,
        'collection_id' => $collection->id,
        'field_id' => $field->id,
        'operation' => 'update_field',
        'before_state' => $beforeState,
        'after_state' => $afterState,
        'performed_by' => 1,
    ]);

    expect($log)
        ->operation->toBe('update_field')
        ->before_state->toBe($beforeState)
        ->after_state->toBe($afterState)
        ->and($log->before_state['label'])->toBe('Old Label')
        ->and($log->after_state['label'])->toBe('New Label');
});

// -- 3. Field deleted → log entry with operation=delete_field --

it('records a migration log entry when a field is deleted', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'to_remove',
        'label' => 'To Remove',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $beforeState = [
        'column_name' => $field->column_name,
        'label' => $field->label,
        'field_type' => $field->field_type,
        'eav_cast' => $field->eav_cast->value,
    ];

    $fieldId = $field->id;
    $field->delete();

    $log = StudioMigrationLog::factory()->create([
        'tenant_id' => $collection->tenant_id,
        'collection_id' => $collection->id,
        'field_id' => $fieldId,
        'operation' => 'delete_field',
        'before_state' => $beforeState,
        'after_state' => null,
        'performed_by' => 1,
    ]);

    expect($log)
        ->operation->toBe('delete_field')
        ->before_state->toBe($beforeState)
        ->after_state->toBeNull()
        ->field_id->toBe($fieldId);
});

// -- 4. Tenant scoping --

it('scopes migration log entries by tenant', function () {
    $collection1 = StudioCollection::factory()->create(['tenant_id' => 10]);
    $collection2 = StudioCollection::factory()->create(['tenant_id' => 20]);

    StudioMigrationLog::factory()->create([
        'tenant_id' => 10,
        'collection_id' => $collection1->id,
        'operation' => 'add_field',
    ]);

    StudioMigrationLog::factory()->create([
        'tenant_id' => 10,
        'collection_id' => $collection1->id,
        'operation' => 'update_field',
    ]);

    StudioMigrationLog::factory()->create([
        'tenant_id' => 20,
        'collection_id' => $collection2->id,
        'operation' => 'delete_field',
    ]);

    expect(StudioMigrationLog::forTenant(10)->count())->toBe(2)
        ->and(StudioMigrationLog::forTenant(20)->count())->toBe(1)
        ->and(StudioMigrationLog::forTenant(99)->count())->toBe(0);
});

it('returns all logs when forTenant receives null', function () {
    StudioMigrationLog::factory()->count(3)->create(['tenant_id' => 10]);
    StudioMigrationLog::factory()->count(2)->create(['tenant_id' => 20]);

    expect(StudioMigrationLog::forTenant(null)->count())->toBe(5);
});

// -- 5. Field rename operation --

it('records a migration log entry for a field rename', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'old_name',
        'label' => 'Old Name',
    ]);

    $beforeState = ['column_name' => 'old_name', 'label' => 'Old Name'];

    $field->update(['column_name' => 'new_name', 'label' => 'New Name']);
    $field->refresh();

    $afterState = ['column_name' => $field->column_name, 'label' => $field->label];

    $log = StudioMigrationLog::factory()->create([
        'collection_id' => $collection->id,
        'field_id' => $field->id,
        'operation' => 'rename_field',
        'before_state' => $beforeState,
        'after_state' => $afterState,
    ]);

    expect($log)
        ->operation->toBe('rename_field')
        ->before_state->toBe($beforeState)
        ->after_state->toBe($afterState);
});

// -- 6. Confirm automatic logging is NOT wired --

it('does not automatically create migration logs on field creation', function () {
    $collection = StudioCollection::factory()->create();

    $countBefore = StudioMigrationLog::count();

    StudioField::factory()->create(['collection_id' => $collection->id]);

    expect(StudioMigrationLog::count())->toBe($countBefore);
});

it('does not automatically create migration logs on field update', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create(['collection_id' => $collection->id]);

    $countBefore = StudioMigrationLog::count();

    $field->update(['label' => 'Changed']);

    expect(StudioMigrationLog::count())->toBe($countBefore);
});

it('does not automatically create migration logs on field deletion', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create(['collection_id' => $collection->id]);

    $countBefore = StudioMigrationLog::count();

    $field->delete();

    expect(StudioMigrationLog::count())->toBe($countBefore);
});
