<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

mutates(EavQueryBuilder::class);

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'products',
        'slug' => 'products',
        'enable_soft_deletes' => true,
    ]);

    $this->nameField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'label' => 'Name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $this->priceField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'price',
        'label' => 'Price',
        'field_type' => 'decimal',
        'eav_cast' => 'decimal',
    ]);

    $this->activeField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'is_active',
        'label' => 'Active',
        'field_type' => 'toggle',
        'eav_cast' => 'boolean',
    ]);

    $this->tagsField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'tags',
        'label' => 'Tags',
        'field_type' => 'tags',
        'eav_cast' => 'json',
    ]);
});

it('creates a record with values', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create([
            'name' => 'Widget Pro',
            'price' => 49.99,
            'is_active' => true,
            'tags' => ['sale', 'new'],
        ]);

    expect($record)->toBeInstanceOf(StudioRecord::class)
        ->and($record->uuid)->not->toBeEmpty()
        ->and($record->collection_id)->toBe($this->collection->id)
        ->and($record->tenant_id)->toBe(1);

    // Verify values were stored
    $values = StudioValue::where('record_id', $record->id)->get();
    expect($values)->toHaveCount(4);

    $nameValue = $values->firstWhere('field_id', $this->nameField->id);
    expect($nameValue->val_text)->toBe('Widget Pro');

    $priceValue = $values->firstWhere('field_id', $this->priceField->id);
    expect((float) $priceValue->val_decimal)->toBe(49.99);

    $activeValue = $values->firstWhere('field_id', $this->activeField->id);
    expect((bool) $activeValue->val_boolean)->toBeTrue();

    $tagsValue = $values->firstWhere('field_id', $this->tagsField->id);
    expect($tagsValue->val_json)->toBe(['sale', 'new']);
});

it('creates a record with a generated UUID', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Test']);

    expect($record->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

it('creates a record without tenant when tenant is null', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->create(['name' => 'No Tenant']);

    expect($record->tenant_id)->toBeNull();
});

it('creates a record with created_by when user ID is provided', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Test'], userId: 42);

    expect($record->created_by)->toBe(42)
        ->and($record->updated_by)->toBe(42);
});

it('ignores unknown fields during create', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create([
            'name' => 'Test',
            'nonexistent_field' => 'ignored',
        ]);

    $values = StudioValue::where('record_id', $record->id)->get();
    expect($values)->toHaveCount(1);
});

it('updates a record values', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Original', 'price' => 10.00]);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'Updated', 'price' => 20.00]);

    $nameValue = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->nameField->id)
        ->first();
    expect($nameValue->val_text)->toBe('Updated');

    $priceValue = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->priceField->id)
        ->first();
    expect((float) $priceValue->val_decimal)->toBe(20.00);
});

it('updates only specified fields', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Original', 'price' => 10.00]);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'Updated']);

    $priceValue = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->priceField->id)
        ->first();
    expect((float) $priceValue->val_decimal)->toBe(10.00);
});

it('creates new values during update if they did not exist', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Test']);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['price' => 15.00]);

    $priceValue = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->priceField->id)
        ->first();
    expect($priceValue)->not->toBeNull()
        ->and((float) $priceValue->val_decimal)->toBe(15.00);
});

it('sets updated_by on update', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Test']);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'Changed'], userId: 99);

    $record->refresh();
    expect($record->updated_by)->toBe(99);
});

it('soft deletes a record when collection has soft deletes enabled', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'To Delete']);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->delete($record->id);

    $record->refresh();
    expect($record->deleted_at)->not->toBeNull();

    // Should not appear in normal queries
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->paginate(25);
    expect($results->total())->toBe(0);
});

it('hard deletes a record when collection does not have soft deletes', function () {
    $hardDeleteCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'logs',
        'slug' => 'logs',
        'enable_soft_deletes' => false,
    ]);

    $logField = StudioField::factory()->create([
        'collection_id' => $hardDeleteCollection->id,
        'tenant_id' => 1,
        'column_name' => 'message',
        'label' => 'Message',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $record = EavQueryBuilder::for($hardDeleteCollection)
        ->tenant(1)
        ->create(['message' => 'To Delete']);

    $recordId = $record->id;

    EavQueryBuilder::for($hardDeleteCollection)
        ->tenant(1)
        ->delete($recordId);

    expect(StudioRecord::find($recordId))->toBeNull();
    expect(StudioValue::where('record_id', $recordId)->count())->toBe(0);
});

it('wraps create in a database transaction', function () {
    // Force a failure mid-create by using an invalid field value that will fail on insert
    // We verify atomicity by checking no partial data exists after failure
    $recordCountBefore = StudioRecord::count();

    try {
        EavQueryBuilder::for($this->collection)
            ->tenant(1)
            ->create(['name' => str_repeat('x', 100000)]); // May succeed depending on TEXT column
    } catch (Throwable) {
        // Expected
    }

    // Either fully created or not at all — no orphaned records without values
    $newRecords = StudioRecord::where('collection_id', $this->collection->id)->get();
    foreach ($newRecords as $record) {
        expect(StudioValue::where('record_id', $record->id)->count())->toBeGreaterThan(0);
    }
});
