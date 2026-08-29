# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Package Overview

Filament Studio (`flexpik/filament-studio`) is a Laravel package that provides a dynamic data model manager for Filament v5 using EAV (Entity-Attribute-Value) storage. Users create collections with custom fields at runtime — no migrations needed per collection.

## Commands

All commands run from the package root (`packages/flexpik/filament-studio/`).

```bash
# Run all tests (uses in-memory SQLite via Orchestra Testbench)
vendor/bin/pest

# Run a single test file
vendor/bin/pest tests/Unit/FieldTypes/Types/TextFieldTypeTest.php

# Run tests matching a name
vendor/bin/pest --filter="it resolves EAV cast"

# Run a specific test suite
vendor/bin/pest --testsuite=Unit
vendor/bin/pest --testsuite=Feature

# Format PHP (Pint lives in host project, not package — must use absolute path or Docker)
docker exec php83 /var/www/html/crud/vendor/bin/pint --dirty --format agent
```

Tests use Pest v4 with Orchestra Testbench. The `TestCase` base class (`tests/TestCase.php`) sets up SQLite in-memory, registers all Filament service providers, converts migration `.php.stub` files to temp `.php` files, and provides `authenticateUser()` for tests needing auth.

## Docker Environment

The project uses Docker containers for PHP execution. The primary container is `php83` (PHP 8.3).

```bash
# Run tests via Docker (required for coverage/mutation testing)
docker exec -w /var/www/html/crud/packages/flexpik/filament-studio -e XDEBUG_MODE=off php83 vendor/bin/pest --compact

# Run mutation testing (requires PCOV, installed in php83 container)
docker exec -w /var/www/html/crud/packages/flexpik/filament-studio -e XDEBUG_MODE=off php83 vendor/bin/pest --mutate --path=src/SomeFile.php

# Run pint via Docker
docker exec php83 /var/www/html/crud/vendor/bin/pint --dirty --format agent
```

- **Host PHP**: Herd Lite PHP 8.4 — works for running tests but has no coverage driver
- **Docker php83**: PHP 8.3 with PCOV installed — required for mutation testing (`pest --mutate`)
- `composer.json` has `platform.php: 8.3.25` set so vendor is compatible with both host and Docker
- Mutation testing uses `pestphp/pest-plugin-mutate` (not infection, which doesn't support Pest)

## Test Coverage

Mutation testing MSI targets ≥80% per module. Run `vendor/bin/pest --mutate --path=src/SomeFile.php` via Docker to check.

Three test suites configured in `tests/Pest.php`:

- **Unit** and **Feature** — use `TestCase` (SQLite in-memory, no Spatie Permission)
- **Integration** — uses `SpatieTestCase` which extends `TestCase` and additionally loads `spatie/laravel-permission` migrations. Use this suite for tests that need Spatie roles/permissions.

When creating new tests that need Spatie permissions, place them in `tests/Integration/` so they automatically get `SpatieTestCase`.

## Architecture

### EAV Storage Pattern

Data is stored across four core tables instead of per-collection tables:

- `studio_collections` — schema definitions (name, slug, tenant_id, settings)
- `studio_fields` — field definitions per collection (field_type, settings JSON, sort_order)
- `studio_records` — individual items (UUID, collection_id, tenant_id)
- `studio_values` — actual data with 6 typed columns: `val_text`, `val_integer`, `val_decimal`, `val_boolean`, `val_datetime`, `val_json`

The `EavCast` enum maps each field type to its storage column.

### Registries (Singleton Pattern)

Two registries manage extensible type systems:

- **FieldTypeRegistry** — 33 built-in field types across 9 categories (text, numeric, boolean, selection, datetime, file, relational, structured, presentation). Each type extends `AbstractFieldType` and implements `toFilamentComponent()`, `toTableColumn()`, `toFilter()`, and `settingsSchema()`.
- **PanelTypeRegistry** — 9 built-in panel types (Metric, List, TimeSeries, BarChart, LineChart, PieChart, Meter, Label, Variable). Each extends `AbstractDmmPanel` with an associated widget class.

Custom types are registered via the plugin API: `FilamentStudioPlugin::make()->fieldTypes([...])->panelTypes([...])`.

### Dynamic Resource System

`DynamicCollectionResource` is a single Filament resource that serves all collections. It resolves the collection from the `{collection_slug}` route parameter and dynamically builds forms, tables, and filters using three service classes:

- **DynamicFormSchemaBuilder** — generates Filament form components from field definitions, handling sections, conditional visibility, and validation
- **DynamicTableColumnsBuilder** — generates table columns from visible fields
- **DynamicFiltersBuilder** — generates table filters from filterable fields

### Query Layer

`EavQueryBuilder` constructs queries across the EAV structure, supporting filtering with `FilterGroup`/`FilterRule` tree logic (22 operators), sorting, pagination, and aggregates.

### Supporting Services

- **VariableResolver** — resolves tokens like `$CURRENT_USER`, `$CURRENT_TENANT`, `$NOW(+1 day)` in panel configs and filters
- **ConditionEvaluator** — evaluates conditional visibility/required/disabled states on form fields

### REST API

`StudioApiController` provides a tenant-scoped CRUD API for collection records, protected by `X-Api-Key` header auth (`ValidateApiKey` middleware). Routes are registered by `StudioApiRouteRegistrar` under a configurable prefix (default `api/studio`). API must be explicitly enabled via config (`api.enabled`). Resources use `RecordResource`/`RecordCollection` for JSON serialization, and OpenAPI docs are generated via transformers in `Api/OpenApi/`.

### Plugin Entry Points

- `FilamentStudioPlugin` (implements Filament `Plugin`) — registers resources, pages, navigation items, and hook callbacks
- `FilamentStudioServiceProvider` — registers singletons (registries, resolvers), migrations, observers, Livewire components, and the authorization policy

### Dashboard System

Dashboards contain panels placed in 5 contexts (`PanelPlacement` enum): Dashboard grid, CollectionHeader, CollectionFooter, RecordHeader, RecordFooter. Each panel type has a corresponding Livewire widget in `src/Widgets/`.

### Permission System

Two levels of authorization, both mediated by `StudioPermission` enum:

- **Global permissions**: `studio.manageFields`, `studio.manageApiKeys`
- **Per-collection permissions**: `studio.collection.{slug}.{viewRecords|createRecord|updateRecord|deleteRecord}` — auto-synced by `PermissionRegistrar` when collections are created/updated/deleted (via observer)

Spatie integration is optional — if `spatie/laravel-permission` is not installed, permission checks gracefully no-op. Three policies enforce access: `StudioCollectionPolicy`, `StudioApiKeyPolicy`, `StudioDashboardPolicy`.

### Hook System

Static hook registry on `FilamentStudioPlugin` for lifecycle events and schema modification:

- Lifecycle: `afterTenantCreated`, `afterCollectionCreated`, `afterFieldAdded`
- Schema modifiers: `modifyFormSchema`, `modifyTableColumns`, `modifyQuery`

Registered via `FilamentStudioPlugin::afterCollectionCreated(fn ($collection) => ...)` etc.

### Custom Validation Rules

`src/Rules/` contains EAV-aware validation rules: `EavUniqueRule` and `EavExistsRule` — used where standard Laravel unique/exists rules can't work against the EAV value table.

### Multi-Tenancy

All major models scope by `tenant_id`. Queries auto-filter to the current tenant.

### Flows (Automation Engine)

`src/Flows/` is a self-contained automation subsystem (triggers → directed graph of operations) with its own models, policies, enums, Filament resource, and namespace `Flexpik\FilamentStudio\Flows\`. It is **opt-in** via config (`flows.enabled`, default false) and built on the `durable-workflow/workflow` package.

**Data model** (tables prefixed `studio_flow*`):
- `studio_flows` — flow definition (trigger config, published graph, status)
- `studio_flow_versions` — immutable published snapshots; flows have a draft + published version (see versioning below)
- `studio_flow_runs` / `studio_flow_run_steps` — execution records forming a run tree; observability columns added by `z_add_observability_columns_to_flow_runs_and_steps`
- `studio_flow_secrets` — encrypted per-flow secrets injected into operation configs
- `studio_flow_audit_log` — security/lifecycle audit trail (`WriteFlowAuditLog`)

**Execution path**: `FlowDispatcher` resolves a flow and creates a `StudioFlowRun`, then runs it sync or queued via `ExecuteFlowJob` (`Jobs/`). `FlowWorkflow` (Engine) walks the operation graph — `GraphWalker` topologically orders nodes — executing each node's `FlowOperation` activity with a `FlowContext`/`OperationContext`. Configs are interpolated through `Engine/Templating/TemplateEngine` (token resolution). `DryRunExecutor` and `StepThroughExecutor` support canvas dry-run and step-through debugging from the designer UI. `LogMaskingService` + `MasksSensitiveValues` redact values matching `flows.sensitive_key_patterns` before persisting step logs.

**Registries** (singletons, like the field/panel registries): `OperationRegistry` and `TriggerRegistry`, both validating config against an optional schema via `ConfigSchemaValidator`. Built-in operations live under `Operations/` by category — `Records` (create/read/update/delete), `Logic` (condition, log message), `Data` (transform payload), `Communication` (HTTP request, send email), `Composition` (trigger another flow, depth-limited by `flows.max_call_depth`). Built-in triggers under `Triggers/` — `ManualTrigger`, `WebhookTrigger`, `CollectionEventTrigger` (via `RecordLifecycleObserver` + `EventSubscriptionRegistry`), and `Schedule/ScheduleTrigger` (cron, dispatched by `DispatchScheduledFlowsCommand`).

**Contracts** (`src/Contracts/Flows/`): operations implement `FlowOperation`/`FlowOperationConfig`, triggers implement `FlowTrigger`/`FlowTriggerConfig`; `DataChain`, `OperationContext`, and `OperationResult` define the execution interface.

**Versioning / draft-publish**: flows are edited as a draft and published into a `StudioFlowVersion`. Services in `src/Flows/Services/` orchestrate this — `SaveFlowDraft`, `PublishFlowVersion`, `RollbackFlowVersion`, `HydrateDraftFromPublished`, plus safety gates `AssertCanPublishDangerousGraph` / `AssertCanEnablePublicWebhook` and `FlowGraphValidator`. `BackfillFlowVersioningCommand` migrates pre-versioning (MVP) flows.

**Webhook security** (`src/Flows/Security/`): `HmacWebhookVerifier` validates signatures with a timestamp window (`flows.webhook_timestamp_window_seconds`) and IP allowlist; `RotateWebhookSecret` rotates secrets. Public webhooks are rate-limited (`flows.webhook_rate_limit_per_minute`).

**UI & API**: `Flows/Filament/Resources/FlowResource` provides the flow designer (`DesignFlow` page), run list/viewer (`ListFlowRuns`, `ViewFlowRun`), and observability widgets in `Flows/Filament/Widgets/` (recent runs, failure rate, duration, top failing). A separate flows REST API is registered by `StudioFlowsApiRouteRegistrar`.

**Plugin API**: third parties register custom operations/triggers via `FilamentStudioPlugin::registerFlowOperation(key, label, activity, configSchema)` and `::registerFlowTrigger(...)` (see `tests/Fixtures/Flows/SlackPackage` for an example extension).

Flow tests live under each suite at `tests/{Unit,Feature,Integration}/Flows/`.

## Key Conventions

- All database tables use the configurable prefix from `config/filament-studio.php` (default: `studio_`)
- Migrations are `.php.stub` files in `database/migrations/` — the TestCase copies them to temp `.php` files for testing
- Field type keys match the `studio_fields.field_type` column value
- Panel type keys match the `studio_panels.panel_type` column value
- Models use `HasUuids` trait where applicable
- Factories exist for all models in `database/factories/`
- Config also controls REST API settings (`api.enabled`, `api.prefix`, `api.rate_limit`)
- The package namespace is `Flexpik\FilamentStudio\` with tests under `Flexpik\FilamentStudio\Tests\`
- CI runs tests against PHP 8.3 and 8.4 (`.github/workflows/tests.yml`)
- Record versioning is opt-in per collection (`enable_versioning` setting) — `RecordVersioningObserver` captures JSON snapshots before/after updates
