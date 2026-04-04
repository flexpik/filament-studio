# Changelog

All notable changes to Filament Studio will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/flexpik/filament-studio/compare/v1.0.3...HEAD
[1.0.3]: https://github.com/flexpik/filament-studio/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/flexpik/filament-studio/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/flexpik/filament-studio/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/flexpik/filament-studio/releases/tag/v1.0.0
