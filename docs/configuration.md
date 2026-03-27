# Configuration

## Config File

After publishing, the configuration file is located at `config/filament-studio.php`:

```php
return [
    // Prefix for all database tables created by Filament Studio
    'table_prefix' => 'studio_',

    // REST API settings
    'api' => [
        'enabled' => env('STUDIO_API_ENABLED', false),
        'prefix'  => env('STUDIO_API_PREFIX', 'api/studio'),
        'rate_limit' => env('STUDIO_API_RATE_LIMIT', 60),
    ],
];
```

| Key | Default | Description |
|-----|---------|-------------|
| `table_prefix` | `studio_` | Prefix for all database tables. Change this to avoid conflicts with existing tables. |
| `api.enabled` | `false` | Enable the auto-generated REST API. |
| `api.prefix` | `api/studio` | URL prefix for API routes. |
| `api.rate_limit` | `60` | Maximum API requests per minute (per API key or IP). |

## Plugin Options

Customize the plugin using the fluent API when registering it:

```php
FilamentStudioPlugin::make()
    ->navigationGroup('Content')
    ->schemaNavigationLabel('Data Models')
    ->enableVersioning()
    ->enableSoftDeletes()
    ->useScout()
    ->enableApi();
```

### Navigation

| Method | Default | Description |
|--------|---------|-------------|
| `navigationGroup(string $group)` | `'Studio'` | Sidebar navigation group label. |
| `schemaNavigationLabel(string $label)` | `'Schema Manager'` | Navigation label for the schema manager page. |

### Feature Flags

| Method | Default | Description |
|--------|---------|-------------|
| `enableVersioning(bool $enabled = true)` | `false` | Enable snapshot-based record version history. Each update creates a version entry with the full record state. |
| `enableSoftDeletes(bool $enabled = true)` | `false` | Enable soft deletes on records. Deleted records are hidden but can be restored. |
| `useScout(bool $enabled = true)` | `false` | Enable Laravel Scout integration for full-text search on records. |
| `enableApi(bool $enabled = true)` | `false` | Enable the REST API for external access to collection data. See [REST API](api.md). |

### Registering Custom Types

Register custom field types and panel types at plugin boot:

```php
FilamentStudioPlugin::make()
    ->fieldTypes([
        'currency' => CurrencyFieldType::class,
        'rating'   => RatingFieldType::class,
    ])
    ->panelTypes([
        CustomMapPanel::class,
    ]);
```

See [Custom Field Types](extending/custom-field-types.md) and [Custom Panel Types](extending/custom-panel-types.md) for implementation details.

### Condition Resolvers

Register custom condition resolvers for dynamic field visibility:

```php
FilamentStudioPlugin::make()
    ->conditionResolver('has_premium', function () {
        return auth()->user()->isPremium();
    }, reactive: true);
```

See [Conditional Logic](conditional-logic.md) for the full condition system.
