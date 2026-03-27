<p align="center">
    <img src="https://raw.githubusercontent.com/flexpik/filament-studio/main/art/preview.png" alt="Filament Studio — Plugin Preview" style="width: 100%; max-width: 800px;" />
</p>

<p align="center">
    <a href="https://packagist.org/packages/flexpik/filament-studio"><img src="https://img.shields.io/packagist/v/flexpik/filament-studio.svg?style=flat-square" alt="Latest Version on Packagist"></a>
    <a href="https://packagist.org/packages/flexpik/filament-studio"><img src="https://img.shields.io/packagist/dt/flexpik/filament-studio.svg?style=flat-square" alt="Total Downloads"></a>
    <a href="https://github.com/flexpik/filament-studio/actions"><img src="https://img.shields.io/github/actions/workflow/status/flexpik/filament-studio/tests.yml?branch=main&label=tests&style=flat-square" alt="Tests"></a>
    <a href="https://github.com/flexpik/filament-studio/blob/main/LICENSE.md"><img src="https://img.shields.io/packagist/l/flexpik/filament-studio.svg?style=flat-square" alt="License"></a>
</p>

# Filament Studio

**A dynamic data model manager for Filament v5 — create collections, define fields, manage records, and build dashboards, all at runtime. No migrations required.**

Filament Studio turns your Filament admin panel into a flexible data platform. Define custom data structures through a visual interface, and the plugin handles the rest: forms, tables, filters, API endpoints, dashboards, and access control — all powered by an EAV (Entity-Attribute-Value) storage engine.

## Why Filament Studio?

- **No migrations per collection** — Add new data types at runtime without touching your codebase
- **Full Filament integration** — Native forms, tables, filters, and actions that look and feel like hand-crafted resources
- **Production-ready** — Multi-tenancy, authorization, versioning, soft deletes, and audit logging out of the box
- **Extensible** — Register custom field types, panel types, condition resolvers, and lifecycle hooks

## Features

### Dynamic Collections

Create and manage data collections with custom fields through the admin UI. Each collection gets a fully functional CRUD interface with forms, tables, and filters — generated dynamically from the field definitions.

**33 built-in field types** across 9 categories:

| Category | Types |
|----------|-------|
| Text | Text, Textarea, Rich Editor, Markdown, Password, Slug, Color, Hidden |
| Numeric | Integer, Decimal, Range |
| Boolean | Checkbox, Toggle |
| Selection | Select, Multi-Select, Radio, Checkbox List, Tags |
| Date & Time | Date, Time, Datetime |
| File | File, Image, Avatar |
| Relational | Belongs To, Has Many, Belongs To Many |
| Structured | Repeater, Builder, Key-Value |
| Presentation | Section Header, Divider, Callout |

### Dashboard Builder

Build data dashboards with **9 panel types**: Metric, List, Time Series, Bar Chart, Line Chart, Pie Chart, Meter, Label, and Variable. Place panels on dashboards (12-column grid), collection pages, or record pages.

Panels support dynamic variables (`$CURRENT_USER`, `$NOW`, `{{custom}}`), aggregate functions (count, sum, avg, min, max), and interactive controls.

### Advanced Filtering

A visual filter builder with **23 operators**, nested AND/OR logic, dynamic variables, and saved filter presets. Operators adapt to data type — text fields get "contains" and "starts with", dates get "before" and "after", JSON fields get "contains any/all/none".

### REST API

Auto-generated RESTful API with API key authentication, per-collection permissions, rate limiting, and OpenAPI documentation via Scramble.

### Conditional Logic

Fields can be conditionally visible, required, or disabled based on form values, user permissions, page context, or custom resolvers — with cycle detection for safety.

### Multi-Tenancy

Full tenant isolation across all models. Every collection, record, dashboard, and API key is scoped to its tenant.

### Record Versioning & Soft Deletes

Optional snapshot-based version history with restore capability. Optional soft deletes to recover deleted records.

### Authorization

Policy-based access control with granular permissions for collections, records, fields, and API keys. Works with Spatie Permission or any Laravel Gate implementation.

## Quick Start

### Install

```bash
composer require flexpik/filament-studio
```

### Publish & Migrate

```bash
php artisan vendor:publish --tag="filament-studio-migrations"
php artisan migrate
```

### Register the Plugin

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

Visit your admin panel — you'll find a new **Studio** section in the sidebar.

### Configure (Optional)

```php
FilamentStudioPlugin::make()
    ->navigationGroup('Content')
    ->enableVersioning()
    ->enableSoftDeletes()
    ->enableApi()
    ->fieldTypes([
        'currency' => CurrencyFieldType::class,
    ])
    ->panelTypes([
        CustomMapPanel::class,
    ]);
```

Publish the config for environment-level settings:

```bash
php artisan vendor:publish --tag="filament-studio-config"
```

## Extending

### Custom Field Types

Create field types by extending `AbstractFieldType`:

```php
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;
use Flexpik\FilamentStudio\Enums\EavCast;

class RatingFieldType extends AbstractFieldType
{
    protected static string $key = 'rating';
    protected static string $label = 'Rating';
    protected static string $icon = 'heroicon-o-star';
    protected static EavCast $eavCast = EavCast::Integer;
    protected static string $category = 'numeric';

    public function settingsSchema(): array { /* ... */ }
    public function toFilamentComponent(): Component { /* ... */ }
    public function toTableColumn(): ?Column { /* ... */ }
    public function toFilter(): ?Filter { /* ... */ }
}
```

### Lifecycle Hooks

React to events and modify generated schemas:

```php
FilamentStudioPlugin::afterCollectionCreated(fn ($collection) => /* ... */);
FilamentStudioPlugin::afterFieldAdded(fn ($field) => /* ... */);

FilamentStudioPlugin::modifyFormSchema(fn (array $schema, $collection) => $schema);
FilamentStudioPlugin::modifyTableColumns(fn (array $columns, $collection) => $columns);
FilamentStudioPlugin::modifyQuery(fn ($query) => $query);
```

## Architecture

Filament Studio uses **EAV (Entity-Attribute-Value) storage** — data is stored across four core tables instead of creating a table per collection:

| Table | Purpose |
|-------|---------|
| `studio_collections` | Schema definitions (name, slug, settings) |
| `studio_fields` | Field definitions per collection (type, settings, validation) |
| `studio_records` | Record entries (UUID, collection, tenant) |
| `studio_values` | Typed data storage (text, integer, decimal, boolean, datetime, JSON columns) |

This approach enables runtime schema changes without migrations while preserving native database sorting and type safety through typed storage columns.

## Documentation

| Guide | Description |
|-------|-------------|
| [Installation](docs/installation.md) | Requirements, setup, and verification |
| [Configuration](docs/configuration.md) | Config file, plugin options, feature flags |
| [Field Types](docs/field-types.md) | All 33 built-in types, EAV storage, field settings |
| [Dashboards & Panels](docs/dashboards.md) | Dashboard builder, 9 panel types, variables |
| [Filtering](docs/filtering.md) | 23 operators, filter trees, saved filters |
| [REST API](docs/api.md) | Endpoints, authentication, permissions, rate limiting |
| [Conditional Logic](docs/conditional-logic.md) | Dynamic visibility, required, and disabled states |
| [Authorization](docs/authorization.md) | Policies, permissions, Spatie integration |
| [Multi-Tenancy](docs/multi-tenancy.md) | Tenant scoping, lifecycle hooks |
| [Record Versioning](docs/versioning.md) | Snapshots, restore, soft deletes |
| [Hooks & Events](docs/hooks.md) | Lifecycle hooks, schema modification |
| [Custom Field Types](docs/extending/custom-field-types.md) | Building your own field types |
| [Custom Panel Types](docs/extending/custom-panel-types.md) | Building your own dashboard panels |

## Requirements

- PHP 8.2+
- Laravel 11+
- Filament v5

## Testing

```bash
vendor/bin/pest
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover a security vulnerability, please send an email to the maintainers. All security vulnerabilities will be promptly addressed.

## Credits

- [Flexpik](https://github.com/flexpik)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). See [LICENSE](LICENSE.md) for details.
