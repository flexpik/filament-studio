# Changelog

All notable changes to Filament Studio will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-03-15

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

[1.0.0]: https://github.com/flexpik/filament-studio/releases/tag/v1.0.0
