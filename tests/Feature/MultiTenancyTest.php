<?php

use Flexpik\FilamentStudio\FilamentStudioPlugin;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioFieldOption;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\CollectionSeeder;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Database\Eloquent\Model;

it('scopes collections by tenant', function () {
    $collectionA = StudioCollection::factory()->create(['tenant_id' => 1, 'name' => 'products', 'slug' => 'products']);
    $collectionB = StudioCollection::factory()->create(['tenant_id' => 2, 'name' => 'products', 'slug' => 'products']);

    $results = StudioCollection::forTenant(1)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($collectionA->id);
});

it('scopes records by tenant in EavQueryBuilder', function () {
    $collectionA = StudioCollection::factory()->create(['tenant_id' => 1]);
    $collectionB = StudioCollection::factory()->create(['tenant_id' => 2]);

    $fieldA = StudioField::factory()->create([
        'collection_id' => $collectionA->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);
    $fieldB = StudioField::factory()->create([
        'collection_id' => $collectionB->id,
        'tenant_id' => 2,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $recordA = StudioRecord::factory()->create([
        'collection_id' => $collectionA->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $recordA->id,
        'field_id' => $fieldA->id,
        'val_text' => 'Tenant A Product',
    ]);

    $recordB = StudioRecord::factory()->create([
        'collection_id' => $collectionB->id,
        'tenant_id' => 2,
    ]);
    StudioValue::factory()->create([
        'record_id' => $recordB->id,
        'field_id' => $fieldB->id,
        'val_text' => 'Tenant B Product',
    ]);

    $results = EavQueryBuilder::for($collectionA)
        ->tenant(1)
        ->select(['name'])
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Tenant A Product');
});

it('prevents tenant A from reading tenant B records', function () {
    $collectionB = StudioCollection::factory()->create(['tenant_id' => 2]);
    $fieldB = StudioField::factory()->create([
        'collection_id' => $collectionB->id,
        'tenant_id' => 2,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);
    $recordB = StudioRecord::factory()->create([
        'collection_id' => $collectionB->id,
        'tenant_id' => 2,
    ]);
    StudioValue::factory()->create([
        'record_id' => $recordB->id,
        'field_id' => $fieldB->id,
        'val_text' => 'Secret',
    ]);

    $results = EavQueryBuilder::for($collectionB)
        ->tenant(1)
        ->select(['name'])
        ->get();

    expect($results)->toHaveCount(0);
});

it('scopes field options by tenant', function () {
    $field = StudioField::factory()->create(['field_type' => 'select']);
    StudioFieldOption::factory()->create(['field_id' => $field->id, 'tenant_id' => 1, 'value' => 'a']);
    StudioFieldOption::factory()->create(['field_id' => $field->id, 'tenant_id' => 2, 'value' => 'b']);

    $options = StudioFieldOption::where('field_id', $field->id)
        ->where('tenant_id', 1)
        ->get();

    expect($options)->toHaveCount(1)
        ->and($options->first()->value)->toBe('a');
});

it('scopes belongs_to options by tenant', function () {
    $authorCollection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'authors']);
    $authorField = StudioField::factory()->create([
        'collection_id' => $authorCollection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $authorA = StudioRecord::factory()->create([
        'collection_id' => $authorCollection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $authorA->id,
        'field_id' => $authorField->id,
        'val_text' => 'Author A',
    ]);

    $authorCollection2 = StudioCollection::factory()->create(['tenant_id' => 2, 'slug' => 'authors']);
    $authorField2 = StudioField::factory()->create([
        'collection_id' => $authorCollection2->id,
        'tenant_id' => 2,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);
    $authorB = StudioRecord::factory()->create([
        'collection_id' => $authorCollection2->id,
        'tenant_id' => 2,
    ]);
    StudioValue::factory()->create([
        'record_id' => $authorB->id,
        'field_id' => $authorField2->id,
        'val_text' => 'Author B',
    ]);

    $options = EavQueryBuilder::for($authorCollection)
        ->tenant(1)
        ->pluck('name', 'uuid');

    expect($options)->toHaveCount(1)
        ->and($options->values()->first())->toBe('Author A');
});

it('seeds default collections for a new tenant', function () {
    $config = [
        [
            'name' => 'contacts',
            'label' => 'Contact',
            'label_plural' => 'Contacts',
            'slug' => 'contacts',
            'icon' => 'heroicon-o-users',
            'fields' => [
                ['column_name' => 'name', 'label' => 'Name', 'field_type' => 'text', 'eav_cast' => 'text', 'is_required' => true],
                ['column_name' => 'email', 'label' => 'Email', 'field_type' => 'text', 'eav_cast' => 'text'],
            ],
        ],
        [
            'name' => 'tasks',
            'label' => 'Task',
            'label_plural' => 'Tasks',
            'slug' => 'tasks',
            'icon' => 'heroicon-o-clipboard-document-check',
            'fields' => [
                ['column_name' => 'title', 'label' => 'Title', 'field_type' => 'text', 'eav_cast' => 'text', 'is_required' => true],
            ],
        ],
    ];

    CollectionSeeder::seedForTenant(tenantId: 99, collections: $config);

    $collections = StudioCollection::where('tenant_id', 99)->get();
    expect($collections)->toHaveCount(2);
    expect($collections->first()->fields)->toHaveCount(2);
    expect($collections->last()->fields)->toHaveCount(1);
});

it('fires afterTenantCreated hook from plugin', function () {
    FilamentStudioPlugin::resetHooks();

    $hookFired = false;
    $receivedTenantId = null;

    FilamentStudioPlugin::afterTenantCreatedHook(function (Model $tenant) use (&$hookFired, &$receivedTenantId) {
        $hookFired = true;
        $receivedTenantId = $tenant->getKey();
    });

    $tenant = new class extends Model
    {
        protected $table = 'users';

        protected $guarded = [];
    };
    $tenant->id = 42;
    $tenant->exists = true;

    FilamentStudioPlugin::fireAfterTenantCreated($tenant);

    expect($hookFired)->toBeTrue()
        ->and($receivedTenantId)->toBe(42);
});
