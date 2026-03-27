<?php

use Flexpik\FilamentStudio\FilamentStudioPlugin;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Pages\CreateCollectionRecord;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Pages\EditCollectionRecord;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Pages\ListCollectionRecords;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Pages\ViewCollectionRecord;
use Illuminate\Foundation\Auth\User;
use Illuminate\Routing\Route;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

/**
 * Set the collection_slug route parameter on the current request
 * so DynamicCollectionResource::resolveCollection() can find it.
 */
function setCollectionSlugRoute(string $slug): void
{
    $route = new Route('GET', 'admin/studio/{collection_slug}', []);
    $route->bind(request());
    $route->setParameter('collection_slug', $slug);
    request()->setRouteResolver(fn () => $route);
}

/**
 * Create a StudioRecord with EAV values for the given collection.
 *
 * @param  array<string, mixed>  $values  column_name => value pairs
 */
function createEavRecord(StudioCollection $collection, array $values = []): StudioRecord
{
    $record = StudioRecord::create([
        'collection_id' => $collection->id,
        'tenant_id' => $collection->tenant_id,
    ]);

    foreach ($values as $columnName => $value) {
        $field = StudioField::query()
            ->where('collection_id', $collection->id)
            ->where('column_name', $columnName)
            ->firstOrFail();

        StudioValue::create([
            'record_id' => $record->id,
            'field_id' => $field->id,
            $field->eavColumn() => $value,
        ]);
    }

    return $record;
}

beforeEach(function () {
    DynamicCollectionResource::resetResolvedCollection();

    $this->user = User::forceCreate(['name' => 'Test', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password')]);
    actingAs($this->user);

    $this->collection = StudioCollection::factory()->create([
        'name' => 'products',
        'label' => 'Product',
        'label_plural' => 'Products',
        'slug' => 'products',
        'icon' => 'heroicon-o-shopping-bag',
        'is_hidden' => false,
    ]);

    $this->nameField = StudioField::factory()->required()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'name',
        'label' => 'Name',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_hidden_in_table' => false,
        'sort_order' => 1,
    ]);

    $this->priceField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'price',
        'label' => 'Price',
        'field_type' => 'decimal',
        'eav_cast' => 'decimal',
        'is_hidden_in_table' => false,
        'sort_order' => 2,
    ]);

    setCollectionSlugRoute('products');
});

// --- Resource Configuration ---

it('has the correct slug pattern', function () {
    expect(DynamicCollectionResource::getSlug())->toBe('studio/{collection_slug}');
});

it('resolves collection from route parameter', function () {
    $resolved = DynamicCollectionResource::resolveCollection();

    expect($resolved->id)->toBe($this->collection->id)
        ->and($resolved->slug)->toBe('products');
});

it('returns collection label as model label', function () {
    expect(DynamicCollectionResource::getModelLabel())->toBe('Product')
        ->and(DynamicCollectionResource::getPluralModelLabel())->toBe('Products');
});

it('returns collection label_plural as navigation label', function () {
    expect(DynamicCollectionResource::getNavigationLabel())->toBe('Products');
});

it('registers all four pages', function () {
    $pages = DynamicCollectionResource::getPages();

    expect($pages)->toHaveKey('index')
        ->and($pages)->toHaveKey('create')
        ->and($pages)->toHaveKey('edit')
        ->and($pages)->toHaveKey('view');
});

// --- List Page ---

it('can render the list page', function () {
    Livewire::test(ListCollectionRecords::class, [
        'collectionSlug' => 'products',
    ])
        ->assertSuccessful();
});

it('displays records in the list table', function () {
    createEavRecord($this->collection, [
        'name' => 'Widget Pro',
    ]);

    Livewire::test(ListCollectionRecords::class, [
        'collectionSlug' => 'products',
    ])
        ->assertSuccessful()
        ->assertSee('Widget Pro');
});

it('does not display records from other collections', function () {
    $otherCollection = StudioCollection::factory()->create([
        'slug' => 'categories',
    ]);

    StudioField::factory()->create([
        'collection_id' => $otherCollection->id,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    createEavRecord($otherCollection, [
        'name' => 'Other Collection Item',
    ]);

    Livewire::test(ListCollectionRecords::class, [
        'collectionSlug' => 'products',
    ])
        ->assertDontSee('Other Collection Item');
});

// --- Create Page ---

it('can render the create page', function () {
    Livewire::test(CreateCollectionRecord::class, [
        'collectionSlug' => 'products',
    ])
        ->assertSuccessful();
});

it('can create a record', function () {
    Livewire::test(CreateCollectionRecord::class, [
        'collectionSlug' => 'products',
    ])
        ->fillForm([
            'name' => 'Widget Pro',
            'price' => 49.99,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $record = StudioRecord::query()
        ->where('collection_id', $this->collection->id)
        ->first();

    expect($record)->not->toBeNull()
        ->and($record->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/')
        ->and($record->created_by)->toBe($this->user->id);

    $nameValue = StudioValue::query()
        ->where('record_id', $record->id)
        ->where('field_id', $this->nameField->id)
        ->first();

    expect($nameValue->val_text)->toBe('Widget Pro');

    $priceValue = StudioValue::query()
        ->where('record_id', $record->id)
        ->where('field_id', $this->priceField->id)
        ->first();

    expect($priceValue->val_decimal)->toBe(49.99);
});

it('validates required fields on create', function () {
    Livewire::test(CreateCollectionRecord::class, [
        'collectionSlug' => 'products',
    ])
        ->fillForm([
            'name' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

it('sets created_by to authenticated user', function () {
    Livewire::test(CreateCollectionRecord::class, [
        'collectionSlug' => 'products',
    ])
        ->fillForm([
            'name' => 'Created By Test',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $record = StudioRecord::query()
        ->where('collection_id', $this->collection->id)
        ->latest('id')
        ->first();

    expect($record->created_by)->toBe($this->user->id);
});

// --- Edit Page ---

it('can render the edit page', function () {
    $record = createEavRecord($this->collection, [
        'name' => 'Edit Me',
    ]);

    Livewire::test(EditCollectionRecord::class, [
        'collectionSlug' => 'products',
        'record' => $record->uuid,
    ])
        ->assertSuccessful()
        ->assertFormSet([
            'name' => 'Edit Me',
        ]);
});

it('can update a record', function () {
    $record = createEavRecord($this->collection, [
        'name' => 'Old Name',
        'price' => 10.00,
    ]);

    Livewire::test(EditCollectionRecord::class, [
        'collectionSlug' => 'products',
        'record' => $record->uuid,
    ])
        ->fillForm([
            'name' => 'New Name',
            'price' => 99.99,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $nameValue = StudioValue::query()
        ->where('record_id', $record->id)
        ->where('field_id', $this->nameField->id)
        ->first();

    expect($nameValue->val_text)->toBe('New Name');
});

it('sets updated_by on edit', function () {
    $record = createEavRecord($this->collection, [
        'name' => 'Update By Test',
    ]);

    Livewire::test(EditCollectionRecord::class, [
        'collectionSlug' => 'products',
        'record' => $record->uuid,
    ])
        ->fillForm([
            'name' => 'Updated Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $record->refresh();
    expect($record->updated_by)->toBe($this->user->id);
});

it('resolves record by UUID not integer ID', function () {
    $record = createEavRecord($this->collection, [
        'name' => 'UUID Test',
    ]);

    $resolved = DynamicCollectionResource::resolveRecordRouteBinding($record->uuid);
    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($record->id);
});

// --- View Page ---

it('can render the view page', function () {
    $record = createEavRecord($this->collection, [
        'name' => 'View Me',
        'price' => 25.50,
    ]);

    Livewire::test(ViewCollectionRecord::class, [
        'collectionSlug' => 'products',
        'record' => $record->uuid,
    ])
        ->assertSuccessful();
});

// --- Sidebar Navigation ---

it('generates navigation items for visible collections', function () {
    StudioCollection::factory()->create([
        'slug' => 'categories',
        'label_plural' => 'Categories',
        'icon' => 'heroicon-o-tag',
        'is_hidden' => false,
    ]);

    StudioCollection::factory()->create([
        'slug' => 'internal-settings',
        'label_plural' => 'Internal Settings',
        'is_hidden' => true,
    ]);

    $plugin = FilamentStudioPlugin::make();
    $navigationItems = $plugin->getCollectionNavigationItems();

    // Should include 'products' (from beforeEach) and 'categories', but not 'internal-settings'
    expect($navigationItems)->toHaveCount(2);

    $labels = collect($navigationItems)->map(fn ($item) => $item->getLabel())->all();
    expect($labels)->toContain('Products')
        ->and($labels)->toContain('Categories')
        ->and($labels)->not->toContain('Internal Settings');
});

it('uses collection icon for navigation item', function () {
    $plugin = FilamentStudioPlugin::make();
    $navigationItems = $plugin->getCollectionNavigationItems();

    $productItem = collect($navigationItems)->first(fn ($item) => $item->getLabel() === 'Products');
    expect($productItem->getIcon())->toBe('heroicon-o-shopping-bag');
});

it('uses default icon when collection has no icon', function () {
    StudioCollection::factory()->create([
        'slug' => 'no-icon',
        'label_plural' => 'No Icon Items',
        'icon' => null,
        'is_hidden' => false,
    ]);

    $plugin = FilamentStudioPlugin::make();
    $navigationItems = $plugin->getCollectionNavigationItems();

    $noIconItem = collect($navigationItems)->first(fn ($item) => $item->getLabel() === 'No Icon Items');
    expect($noIconItem->getIcon())->toBe('heroicon-o-table-cells');
});

it('uses configured navigation group', function () {
    $plugin = FilamentStudioPlugin::make()
        ->navigationGroup('My Data');

    expect($plugin->getNavigationGroup())->toBe('My Data');
});
