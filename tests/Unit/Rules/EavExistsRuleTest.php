<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Rules\EavExistsRule;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'authors',
        'slug' => 'authors',
    ]);

    $this->existingRecord = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
});

it('passes when record UUID exists in the collection', function () {
    $rule = new EavExistsRule(
        collection: $this->collection,
        tenantId: 1,
    );

    $validator = Validator::make(
        ['author' => $this->existingRecord->uuid],
        ['author' => [$rule]]
    );

    expect($validator->passes())->toBeTrue();
});

it('fails when record UUID does not exist', function () {
    $rule = new EavExistsRule(
        collection: $this->collection,
        tenantId: 1,
    );

    $validator = Validator::make(
        ['author' => '00000000-0000-0000-0000-000000000000'],
        ['author' => [$rule]]
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('author'))->toContain('does not exist');
});

it('scopes by tenant', function () {
    $rule = new EavExistsRule(
        collection: $this->collection,
        tenantId: 99, // Different tenant
    );

    $validator = Validator::make(
        ['author' => $this->existingRecord->uuid],
        ['author' => [$rule]]
    );

    expect($validator->fails())->toBeTrue();
});

it('scopes by collection', function () {
    $otherCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'categories',
        'slug' => 'categories',
    ]);

    $rule = new EavExistsRule(
        collection: $otherCollection,
        tenantId: 1,
    );

    $validator = Validator::make(
        ['author' => $this->existingRecord->uuid],
        ['author' => [$rule]]
    );

    expect($validator->fails())->toBeTrue();
});

it('excludes soft-deleted records', function () {
    $this->existingRecord->update(['deleted_at' => now()]);

    $rule = new EavExistsRule(
        collection: $this->collection,
        tenantId: 1,
    );

    $validator = Validator::make(
        ['author' => $this->existingRecord->uuid],
        ['author' => [$rule]]
    );

    expect($validator->fails())->toBeTrue();
});

it('passes validation with null tenant when tenancy is disabled', function () {
    $noTenantCollection = StudioCollection::factory()->create([
        'tenant_id' => null,
        'name' => 'global_tags',
        'slug' => 'global-tags',
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $noTenantCollection->id,
        'tenant_id' => null,
    ]);

    $rule = new EavExistsRule(
        collection: $noTenantCollection,
        tenantId: null,
    );

    $validator = Validator::make(
        ['tag' => $record->uuid],
        ['tag' => [$rule]]
    );

    expect($validator->passes())->toBeTrue();
});
