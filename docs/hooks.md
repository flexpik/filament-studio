# Hooks & Events

Filament Studio provides a hook system for extending behavior at key lifecycle points and modifying dynamically generated schemas.

## Lifecycle Hooks

### Collection Created

Fired when a new collection is created:

```php
FilamentStudioPlugin::afterCollectionCreated(function (StudioCollection $collection) {
    // Seed default fields, send notifications, sync external systems
    Notification::send($admins, new CollectionCreatedNotification($collection));
});
```

### Field Added

Fired when a new field is added to a collection:

```php
FilamentStudioPlugin::afterFieldAdded(function (StudioField $field) {
    // Validate constraints, trigger data sync, update search index
    Log::info("Field '{$field->label}' added to collection #{$field->collection_id}");
});
```

### Tenant Created

Fired when a new tenant is created (instance-level hook):

```php
FilamentStudioPlugin::make()
    ->afterTenantCreated(function (Model $tenant) {
        CollectionSeeder::seedForTenant($tenant->id, [...]);
    });
```

## Schema Modification Hooks

These hooks let you modify the dynamically generated forms, tables, and queries for all collections.

### Modify Form Schema

Add, remove, or reorder form components:

```php
FilamentStudioPlugin::modifyFormSchema(function (array $schema, StudioCollection $collection) {
    // Add a custom component at the end
    $schema[] = Section::make('Metadata')
        ->schema([
            Placeholder::make('id')->content(fn ($record) => $record?->uuid),
        ]);

    return $schema;
});
```

### Modify Table Columns

Add, remove, or reorder table columns:

```php
FilamentStudioPlugin::modifyTableColumns(function (array $columns, StudioCollection $collection) {
    // Add a computed column
    $columns[] = TextColumn::make('age')
        ->label('Days Old')
        ->getStateUsing(fn ($record) => $record->created_at->diffInDays());

    return $columns;
});
```

### Modify Query

Apply global scopes or filters to all collection queries:

```php
FilamentStudioPlugin::modifyQuery(function ($query) {
    // Only show records created in the last 30 days
    return $query->where('created_at', '>=', now()->subDays(30));
});
```

## Resetting Hooks

For testing or reinitialization, all static hooks can be reset:

```php
FilamentStudioPlugin::resetHooks();
```

## Observer

The `RecordVersioningObserver` listens to `StudioRecord` update events and creates version snapshots when versioning is enabled. See [Record Versioning](versioning.md) for details.
