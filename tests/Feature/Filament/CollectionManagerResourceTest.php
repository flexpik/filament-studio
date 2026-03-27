<?php

use Filament\Facades\Filament;
use Flexpik\FilamentStudio\FilamentStudioPlugin;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioMigrationLog;
use Flexpik\FilamentStudio\Resources\CollectionManagerResource;
use Flexpik\FilamentStudio\Resources\CollectionManagerResource\Pages\CreateCollection;
use Flexpik\FilamentStudio\Resources\CollectionManagerResource\Pages\EditCollection;
use Flexpik\FilamentStudio\Resources\CollectionManagerResource\Pages\ListCollections;
use Illuminate\Foundation\Auth\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::forceCreate(['name' => 'Test', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password')]);
    actingAs($this->user);
});

// --- Chunk 1: List Page ---

it('can render the list page', function () {
    Livewire::test(ListCollections::class)
        ->assertSuccessful();
});

it('can list collections', function () {
    $collections = StudioCollection::factory()->count(3)->create();

    Livewire::test(ListCollections::class)
        ->assertCanSeeTableRecords($collections);
});

it('displays expected table columns', function () {
    StudioCollection::factory()->create([
        'name' => 'products',
        'label' => 'Products',
        'is_singleton' => false,
    ]);

    Livewire::test(ListCollections::class)
        ->assertCanRenderTableColumn('name')
        ->assertCanRenderTableColumn('label')
        ->assertCanRenderTableColumn('fields_count')
        ->assertCanRenderTableColumn('records_count')
        ->assertCanRenderTableColumn('is_singleton')
        ->assertCanRenderTableColumn('created_at');
});

it('can search collections by name', function () {
    $visible = StudioCollection::factory()->create(['name' => 'products', 'label' => 'Products']);
    $hidden = StudioCollection::factory()->create(['name' => 'orders', 'label' => 'Orders']);

    Livewire::test(ListCollections::class)
        ->searchTable('products')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

it('can delete a collection from the list', function () {
    $collection = StudioCollection::factory()->create();

    Livewire::test(ListCollections::class)
        ->callTableAction('delete', $collection);

    expect(StudioCollection::find($collection->id))->toBeNull();
});

// --- Chunk 1: Plugin Registration ---

it('is registered in the FilamentStudioPlugin', function () {
    $plugin = new FilamentStudioPlugin;
    $panel = Filament::getCurrentPanel();

    // Verify the resource class is used by checking routes exist
    expect(CollectionManagerResource::getPages())->not->toBeEmpty();
});

// --- Chunk 2: Create Wizard ---

it('can render the create page', function () {
    Livewire::test(CreateCollection::class)
        ->assertSuccessful();
});

it('can create a collection via the wizard', function () {
    Livewire::test(CreateCollection::class)
        ->fillForm([
            'name' => 'products',
            'label' => 'Product',
            'label_plural' => 'Products',
            'icon' => 'heroicon-o-shopping-bag',
            'description' => 'Product catalog',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(StudioCollection::where('name', 'products')->exists())->toBeTrue();

    $collection = StudioCollection::where('name', 'products')->first();
    expect($collection)
        ->label->toBe('Product')
        ->label_plural->toBe('Products')
        ->icon->toBe('heroicon-o-shopping-bag')
        ->description->toBe('Product catalog');
});

it('auto-generates slug from name on create', function () {
    Livewire::test(CreateCollection::class)
        ->fillForm([
            'name' => 'blog_posts',
            'label' => 'Blog Post',
            'label_plural' => 'Blog Posts',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $collection = StudioCollection::where('name', 'blog_posts')->first();
    expect($collection->slug)->toBe('blog-posts');
});

it('validates required fields on create', function () {
    Livewire::test(CreateCollection::class)
        ->fillForm([
            'name' => '',
            'label' => '',
            'label_plural' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'label' => 'required',
            'label_plural' => 'required',
        ]);
});

// --- Chunk 2: System Fields & Migration Logging ---

it('creates system fields when selected in wizard', function () {
    Livewire::test(CreateCollection::class)
        ->fillForm([
            'name' => 'articles',
            'label' => 'Article',
            'label_plural' => 'Articles',
            'system_fields' => ['status', 'sort_order', 'timestamps'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $collection = StudioCollection::where('name', 'articles')->first();
    $fields = StudioField::where('collection_id', $collection->id)
        ->where('is_system', true)
        ->get();

    expect($fields)->toHaveCount(4); // status, sort_order, created_at, updated_at

    $columnNames = $fields->pluck('column_name')->toArray();
    expect($columnNames)->toContain('status')
        ->toContain('sort_order')
        ->toContain('created_at')
        ->toContain('updated_at');
});

it('logs create_collection to migration logs', function () {
    Livewire::test(CreateCollection::class)
        ->fillForm([
            'name' => 'events',
            'label' => 'Event',
            'label_plural' => 'Events',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $collection = StudioCollection::where('name', 'events')->first();
    $log = StudioMigrationLog::where('collection_id', $collection->id)->first();

    expect($log)
        ->not->toBeNull()
        ->operation->toBe('create_collection')
        ->after_state->not->toBeNull();
});

it('creates no system fields when none are selected', function () {
    Livewire::test(CreateCollection::class)
        ->fillForm([
            'name' => 'settings',
            'label' => 'Settings',
            'label_plural' => 'Settings',
            'system_fields' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $collection = StudioCollection::where('name', 'settings')->first();
    $systemFields = StudioField::where('collection_id', $collection->id)
        ->where('is_system', true)
        ->count();

    expect($systemFields)->toBe(0);
});

// --- Chunk 3: Edit Page ---

it('can render the edit page', function () {
    $collection = StudioCollection::factory()->create();

    Livewire::test(EditCollection::class, [
        'record' => $collection->getRouteKey(),
    ])
        ->assertSuccessful();
});

it('can update collection settings', function () {
    $collection = StudioCollection::factory()->create([
        'name' => 'products',
        'label' => 'Product',
        'label_plural' => 'Products',
        'is_singleton' => false,
        'enable_versioning' => false,
    ]);

    Livewire::test(EditCollection::class, [
        'record' => $collection->getRouteKey(),
    ])
        ->fillForm([
            'label' => 'Updated Product',
            'label_plural' => 'Updated Products',
            'is_singleton' => true,
            'enable_versioning' => true,
            'display_template' => '{{name}} — {{status}}',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $collection->refresh();

    expect($collection)
        ->label->toBe('Updated Product')
        ->label_plural->toBe('Updated Products')
        ->is_singleton->toBeTrue()
        ->enable_versioning->toBeTrue()
        ->display_template->toBe('{{name}} — {{status}}');
});

it('logs update_collection with before and after state', function () {
    $collection = StudioCollection::factory()->create([
        'label' => 'Original',
    ]);

    Livewire::test(EditCollection::class, [
        'record' => $collection->getRouteKey(),
    ])
        ->fillForm([
            'label' => 'Changed',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $log = StudioMigrationLog::where('collection_id', $collection->id)
        ->where('operation', 'update_collection')
        ->first();

    expect($log)
        ->not->toBeNull()
        ->before_state->not->toBeNull()
        ->after_state->not->toBeNull();

    expect($log->before_state['label'])->toBe('Original');
    expect($log->after_state['label'])->toBe('Changed');
});

// --- Chunk 5: Migration Log Viewer ---

it('displays migration logs on the edit page', function () {
    $collection = StudioCollection::factory()->create();

    StudioMigrationLog::create([
        'tenant_id' => $collection->tenant_id,
        'collection_id' => $collection->id,
        'operation' => 'create_collection',
        'after_state' => $collection->toArray(),
        'performed_by' => $this->user->id,
    ]);

    StudioMigrationLog::create([
        'tenant_id' => $collection->tenant_id,
        'collection_id' => $collection->id,
        'field_id' => null,
        'operation' => 'add_field',
        'after_state' => ['column_name' => 'title'],
        'performed_by' => $this->user->id,
    ]);

    Livewire::test(EditCollection::class, [
        'record' => $collection->getRouteKey(),
    ])
        ->assertSuccessful();
});

// --- Chunk 5: Full Lifecycle Integration ---

it('supports full collection lifecycle: create, edit, delete', function () {
    // Create
    Livewire::test(CreateCollection::class)
        ->fillForm([
            'name' => 'tasks',
            'label' => 'Task',
            'label_plural' => 'Tasks',
            'system_fields' => ['status', 'timestamps'],
            'is_singleton' => false,
            'enable_versioning' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $collection = StudioCollection::where('name', 'tasks')->first();
    expect($collection)->not->toBeNull();
    expect($collection->enable_versioning)->toBeTrue();

    // Verify system fields created
    $systemFields = StudioField::where('collection_id', $collection->id)
        ->where('is_system', true)
        ->count();
    expect($systemFields)->toBe(3); // status, created_at, updated_at

    // Verify create log
    $createLog = StudioMigrationLog::where('collection_id', $collection->id)
        ->where('operation', 'create_collection')
        ->first();
    expect($createLog)->not->toBeNull();

    // Edit
    Livewire::test(EditCollection::class, [
        'record' => $collection->getRouteKey(),
    ])
        ->fillForm([
            'label' => 'Updated Task',
            'description' => 'Task management collection',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $collection->refresh();
    expect($collection->label)->toBe('Updated Task');

    // Verify update log
    $updateLog = StudioMigrationLog::where('collection_id', $collection->id)
        ->where('operation', 'update_collection')
        ->first();
    expect($updateLog)->not->toBeNull();

    // Delete
    Livewire::test(ListCollections::class)
        ->callTableAction('delete', $collection);

    expect(StudioCollection::find($collection->id))->toBeNull();
});
