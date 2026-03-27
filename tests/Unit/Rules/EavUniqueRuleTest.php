<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Rules\EavUniqueRule;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'products',
        'slug' => 'products',
    ]);

    $this->emailField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'email',
        'label' => 'Email',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_unique' => true,
    ]);

    // Create existing record with email
    $this->existingRecord = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $this->existingRecord->id,
        'field_id' => $this->emailField->id,
        'val_text' => 'taken@example.com',
    ]);
});

it('fails when value already exists for field in collection', function () {
    $rule = new EavUniqueRule(
        field: $this->emailField,
        collection: $this->collection,
        tenantId: 1,
    );

    $validator = Validator::make(
        ['email' => 'taken@example.com'],
        ['email' => [$rule]]
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('email'))->toContain('already been taken');
});

it('passes when value does not exist', function () {
    $rule = new EavUniqueRule(
        field: $this->emailField,
        collection: $this->collection,
        tenantId: 1,
    );

    $validator = Validator::make(
        ['email' => 'new@example.com'],
        ['email' => [$rule]]
    );

    expect($validator->passes())->toBeTrue();
});

it('passes when ignoring a specific record ID (edit mode)', function () {
    $rule = new EavUniqueRule(
        field: $this->emailField,
        collection: $this->collection,
        tenantId: 1,
        ignoreRecordId: $this->existingRecord->id,
    );

    $validator = Validator::make(
        ['email' => 'taken@example.com'],
        ['email' => [$rule]]
    );

    expect($validator->passes())->toBeTrue();
});

it('fails when ignoring a different record ID', function () {
    $rule = new EavUniqueRule(
        field: $this->emailField,
        collection: $this->collection,
        tenantId: 1,
        ignoreRecordId: 99999,
    );

    $validator = Validator::make(
        ['email' => 'taken@example.com'],
        ['email' => [$rule]]
    );

    expect($validator->fails())->toBeTrue();
});

it('scopes uniqueness check by tenant', function () {
    // Same email in different tenant should be fine
    $rule = new EavUniqueRule(
        field: $this->emailField,
        collection: $this->collection,
        tenantId: 99, // Different tenant
    );

    $validator = Validator::make(
        ['email' => 'taken@example.com'],
        ['email' => [$rule]]
    );

    expect($validator->passes())->toBeTrue();
});

it('scopes uniqueness check by collection', function () {
    $otherCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'users',
        'slug' => 'users',
    ]);

    $otherEmailField = StudioField::factory()->create([
        'collection_id' => $otherCollection->id,
        'tenant_id' => 1,
        'column_name' => 'email',
        'label' => 'Email',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_unique' => true,
    ]);

    // Same email in different collection should be fine
    $rule = new EavUniqueRule(
        field: $otherEmailField,
        collection: $otherCollection,
        tenantId: 1,
    );

    $validator = Validator::make(
        ['email' => 'taken@example.com'],
        ['email' => [$rule]]
    );

    expect($validator->passes())->toBeTrue();
});

it('works with integer field type', function () {
    $codeField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'code',
        'label' => 'Code',
        'field_type' => 'integer',
        'eav_cast' => 'integer',
        'is_unique' => true,
    ]);

    StudioValue::factory()->create([
        'record_id' => $this->existingRecord->id,
        'field_id' => $codeField->id,
        'val_integer' => 12345,
    ]);

    $rule = new EavUniqueRule(
        field: $codeField,
        collection: $this->collection,
        tenantId: 1,
    );

    $validator = Validator::make(
        ['code' => 12345],
        ['code' => [$rule]]
    );

    expect($validator->fails())->toBeTrue();
});
