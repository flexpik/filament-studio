# Installation

## Requirements

- PHP 8.2 or higher
- Laravel 11 or higher
- Filament v5

## Install via Composer

```bash
composer require flexpik/filament-studio
```

## Publish & Run Migrations

```bash
php artisan vendor:publish --tag="filament-studio-migrations"
php artisan migrate
```

This creates the following tables (prefixed with `studio_` by default):

| Table | Purpose |
|-------|---------|
| `studio_collections` | Collection schema definitions |
| `studio_fields` | Field definitions per collection |
| `studio_field_options` | Select/radio/checkbox options |
| `studio_records` | Individual record entries |
| `studio_values` | EAV data storage (typed columns) |
| `studio_record_versions` | Version history snapshots |
| `studio_dashboards` | Dashboard definitions |
| `studio_panels` | Dashboard panel configurations |
| `studio_api_keys` | REST API authentication keys |
| `studio_saved_filters` | Persisted filter configurations |
| `studio_migration_logs` | Schema change audit trail |

## Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag="filament-studio-config"
```

This publishes `config/filament-studio.php`. See [Configuration](configuration.md) for all options.

## Register the Plugin

Add the plugin to your Filament panel provider:

```php
use Flexpik\FilamentStudio\FilamentStudioPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentStudioPlugin::make(),
        ]);
}
```

Your panel now includes a **Schema Manager** for creating collections and a dynamic CRUD interface for managing records.

## Verify Installation

After registering the plugin, visit your Filament admin panel. You should see a "Studio" navigation group with a "Schema Manager" link. From there you can create your first collection.
