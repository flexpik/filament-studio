# Changelog

All notable changes to Filament Studio will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- MCP server foundation (`src/Mcp/`) — opt-in via `mcp.enabled` config flag.
- Both HTTP/SSE (mounted at `/ai/studio`) and stdio (`php artisan mcp:start studio`) transports.
- Reuse of `StudioApiKey` for MCP auth with a new `_studio.*` management-scope namespace (`manage_collections`, `manage_dashboards`, `manage_filters`, `manage_api_keys`, `read_schema`).
- Six capability-discovery Resources: `studio://info`, `studio://field-types`, `studio://field-types/{key}`, `studio://panel-types`, `studio://panel-types/{key}`, `studio://operators`.
- "MCP Management Scopes" section on the API Key edit form (visible only when `mcp.enabled`).
- New `studio-mcp` rate limiter (per X-Api-Key, configurable via `mcp.http.rate_limit`).
- 12 schema-management MCP tools: `studio_list_collections`, `studio_get_collection`, `studio_create_collection`, `studio_update_collection`, `studio_preview_delete_collection`, `studio_delete_collection`, `studio_create_field`, `studio_update_field`, `studio_preview_delete_field`, `studio_delete_field`, `studio_reorder_fields`, `studio_set_field_options`.
- Cache-backed per-tenant confirm-token flow for destructive collection/field deletes (5-minute TTL, one-time use, configurable via `mcp.confirm_token_ttl`).
- Action layer (`Mcp/Actions/`) for collection/field/field-option mutations — framework-agnostic, reusable.
- `StudioMcpExceptionHandler` mapping domain exceptions to JSON-RPC error codes per §6 of the design spec.
- `McpSerializer` for canonical entity shapes shared across all schema-management tools.
- Domain exception classes: `ConfirmTokenInvalidException`, `EavCastConflictException`, `IntegrityException`, `StudioNotFoundException` — each with `mcpCode()` and `mcpData()` for uniform handler calls.
- `mcpCallTool($apiKey, $toolClass, $input)` Pest helper for MCP tool tests.
- End-to-end HTTP integration test (`SchemaDesignFlowTest`) covering create→field→preview→delete flow.
- Cross-tenant confirm-token isolation test.

### Notes
- No tools are registered yet beyond schema management; tools come in P3 (records), P4 (dashboards/panels), P5 (saved filters / API keys / polish).

## [1.2.0] - 2026-04-15

### Added

- **Multilingual Support** — Opt-in per-locale content for translatable fields, controlled by a global `locales.enabled` config toggle
- **Locale Column on EAV Values** — `studio_values` table gains a `locale` column with a composite unique constraint `[record_id, field_id, locale]`, enabling per-locale storage without schema-per-field changes
- **Per-Field Translatable Flag** — Each field can be individually marked as translatable via the `is_translatable` toggle in the field editor; non-translatable fields store a single value regardless of locale
- **Per-Collection Locale Settings** — Collections define their own `supported_locales` subset and a `default_locale` for fallback behavior
- **LocaleResolver Service** — Centralized locale detection with a 4-level priority chain: `?locale=` query param > `X-Locale` header > session > collection/global default
- **Locale-Aware EavQueryBuilder** — New `locale()` fluent method on the query builder. `create()`, `update()`, `getRecordData()`, and `toEloquentQuery()` all respect the active locale with automatic fallback to the default locale for missing translations
- **Fallback Metadata** — `getRecordDataWithMeta()` returns both data and a `fallbacks` array indicating which fields are displaying fallback values from the default locale
- **All-Locale Data Retrieval** — `getAllLocaleData()` returns translatable fields as nested locale maps (`{"en": "Hello", "fr": "Bonjour"}`) and non-translatable fields as plain values
- **Admin Locale Switcher** — Livewire-powered locale toggle buttons displayed in the record edit page header; switching locale persists to session and reloads form data for the selected locale
- **Collection Multilingual Toggle** — New "Multilingual" section in collection settings with locale multi-select and default locale picker, only visible when `locales.enabled` is true
- **Field Translatable Toggle** — "Translatable" toggle in the field behavior settings section, only visible when the parent collection has multilingual enabled
- **REST API Locale Support** — API endpoints accept `?locale=` query param or `X-Locale` header for locale-specific reads/writes; responses include `_meta.locale` and `_meta.fallbacks` metadata
- **API All-Locales Mode** — `GET ?all_locales=true` returns translatable fields as nested locale objects in a single response
- **OpenAPI Locale Documentation** — API docs now show `locale`, `X-Locale`, and `all_locales` parameters with enum dropdowns when multilingual is enabled, plus `_meta` response schema and locale hints in operation descriptions
- **Multi-Locale Version Snapshots** — Record version snapshots capture all locale values for translatable fields as nested objects; version restore correctly writes back all locale rows
- **UI Metadata Translations** — Field labels, placeholders, and hints resolve from the `translations` JSON column per active locale via `StudioField::getTranslatedAttribute()`

### Changed

- **EavQueryBuilder** `getRecordData()` now delegates to `getRecordDataWithMeta()` internally
- **RecordVersioningObserver** `buildSnapshot()` produces nested locale structures for translatable fields
- **RecordResource** API serialization is now locale-aware, resolving the active locale from the request
- **StudioApiController** `show()`, `store()`, and `update()` endpoints return JSON responses with `_meta` locale metadata
- **StudioDocumentTransformer** conditionally adds locale parameters and `_meta` response schemas when multilingual is enabled

## [1.1.0] - 2026-04-11

### Added

- **Spatie Permission Integration** — Automatic per-collection permission sync when `spatie/laravel-permission` is installed. Each collection gets four granular permissions (`viewRecords`, `createRecord`, `updateRecord`, `deleteRecord`) under the `studio.collection.{slug}.*` namespace
- **StudioPermission Enum** — Single source of truth for all permission strings, with helpers for generating collection-specific permission names and labels
- **PermissionRegistrar Service** — Detects Spatie Permission at runtime, syncs global and per-collection permissions, and handles collection renames by removing old and creating new permission entries
- **Auto-Sync via Observer** — Permissions are automatically created when a collection is created, updated on rename, and removed on deletion
- **Policy Enforcement** — `StudioCollectionPolicy` checks per-collection permissions for view, create, update, and delete operations. `StudioApiKeyPolicy` and `StudioDashboardPolicy` enforce global permission checks
- **UI Permission Enforcement** — Navigation items, Create/Edit/Delete actions, and dashboard pages are hidden when the user lacks the required permission
- **Collection Permissions Screenshot** — Added `art/collection-permissions.png` showing the per-collection RBAC interface

### Changed

- **Policies refactored** to use `StudioPermission` enum instead of hardcoded permission strings
- **Dashboard page** uses Shield page permission for access control

## [1.0.3] - 2026-04-04

### Added

- **Dashboard Panels Relation Manager** — Manage panels directly within dashboard edit pages with full CRUD, ordering, and panel type configuration
- **PanelsRelationManagerTest** — 174-line test covering panel creation, editing, deletion, and listing within dashboards
- **Inline Filter Builder Views** — Alpine-based filter builder variants (`filter-builder-alpine`, `filter-builder-inline`, `filter-group-inline`, `filter-rule-row-inline`) for alternative filter UX

### Changed

- **Filter Builder** rewritten with improved UX — enhanced filter-builder, filter-group, and filter-rule-row Blade views
- **Version History View** — Layout and display improvements
- **EavQueryBuilder** — Expanded query capabilities and performance refinements
- **Collection Record Pages** — Improved Edit, List, and View pages with better layout and functionality
- **API Settings Resource** — Updated resource and ListApiKeys page
- **Field Types** improved: Avatar (validation), CheckboxList (options handling), File (upload config), Image (preview), MultiSelect (search), Password (confirmation), RichEditor (toolbar), Tags (separators)
- **Dashboard Panel Types** refined: BarChart, LineChart, List, Meter, Metric, PieChart, TimeSeries — consistent configuration patterns
- **StudioRecordVersion** model updates

### Fixed

- **Concurrency test reliability** — Fixed timing issues in DeleteWithIntegrityRace and HardDeleteRace tests
- **API cross-tenant access** — Test cleanup for ApiCrossTenantAccessTest
- **RecordResource test** — Removed redundant assertion
- **RichEditorFieldType test** — Fixed expected toolbar config

## [1.0.2] - 2026-03-28

### Added

- **Per-Collection API Documentation** — Each collection now has an `api_enabled` toggle in the Data Models editor. When enabled, the collection's CRUD endpoints appear in the OpenAPI/Swagger documentation at `/docs/api` with field-specific request/response schemas
- **Dynamic OpenAPI Schema Generation** — `StudioDocumentTransformer` generates per-collection paths with typed schemas derived from each collection's field definitions, including proper EAV cast mapping (text→string, integer→integer, decimal→number, boolean→boolean, datetime→date-time, json→object)
- **API Key Display & Copy** — After creating or regenerating an API key, the plain key is shown in a dedicated form section with a monospace input and Filament's built-in copy-to-clipboard button
- **Regenerate Key Action** — "Regenerate Key" button on the API key edit page with confirmation dialog; immediately invalidates the old key
- **`api_enabled` column** on `studio_collections` table with migration, model cast, `scopeApiEnabled()` query scope, and `apiEnabled()` factory state

### Fixed

- **API route registration boot order** — Fixed timing issue where `FilamentStudioPlugin::boot()` set the API config after the service provider's `booted()` callback had already checked it, preventing routes from registering
- **OpenAPI path prefix duplication** — Stripped Scramble's `api_path` prefix from generated paths to avoid double-prefixed URLs (e.g., `/api/api/studio/...`) that caused 404s from the Try It button
- **API gate for disabled collections** — `StudioApiController::resolveCollection()` now rejects requests to collections with `api_enabled=false` (returns 404)
- **Duplicate X-Api-Key parameters** — `StudioOperationTransformer` skips operations that already have the header parameter added by the document transformer

## [1.0.1] - 2026-03-28

### Changed

- Minimum PHP version raised from 8.2 to 8.3 (required by Pest v4)
- Added package metadata: `authors`, `homepage`, and `support` to `composer.json`
- Fixed release date for v1.0.0 in CHANGELOG

## [1.0.0] - 2026-03-27

### Added

- **Dynamic Collections** — Create and manage data collections at runtime through the Schema Manager
- **33 Built-in Field Types** across 9 categories: text, numeric, boolean, selection, date/time, file, relational, structured, and presentation
- **EAV Storage Engine** with 6 typed value columns (`val_text`, `val_integer`, `val_decimal`, `val_boolean`, `val_datetime`, `val_json`)
- **Dynamic Form Builder** — Auto-generates Filament forms from field definitions with validation, conditional visibility, and sections
- **Dynamic Table Builder** — Auto-generates sortable, searchable, and filterable table columns
- **Dashboard Builder** with 9 panel types: Metric, List, Time Series, Bar Chart, Line Chart, Pie Chart, Meter, Label, and Variable
- **Panel Placement System** — Place panels in 5 contexts: Dashboard, Collection Header/Footer, Record Header/Footer
- **Advanced Filtering** with 22 operators, saved filters, and dynamic value resolution
- **Record Versioning** — Optional snapshot-based version history
- **Multi-Tenancy** — Tenant-scoped collections, records, and dashboards
- **Hook System** — Lifecycle hooks for `afterCollectionCreated`, `afterFieldAdded`, `modifyFormSchema`, `modifyTableColumns`, and `modifyQuery`
- **Custom Condition Resolvers** for dynamic field visibility, required, and disabled states
- **Dynamic Variables** — Runtime token resolution (`$CURRENT_USER`, `$CURRENT_TENANT`, `$NOW`, etc.)
- **Policy-based Authorization** on collections
- **Activity Logging** — Optional integration with `spatie/activitylog`
- **Soft Deletes** — Optional soft delete support for records
- **Extensible Architecture** — Register custom field types, panel types, and condition resolvers via the plugin API
- **Configurable Table Prefix** to avoid naming conflicts
- **Migration Log Tracking** for schema change auditing

[Unreleased]: https://github.com/flexpik/filament-studio/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/flexpik/filament-studio/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/flexpik/filament-studio/compare/v1.0.4...v1.1.0
[1.0.3]: https://github.com/flexpik/filament-studio/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/flexpik/filament-studio/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/flexpik/filament-studio/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/flexpik/filament-studio/releases/tag/v1.0.0
