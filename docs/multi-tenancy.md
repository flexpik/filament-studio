# Multi-Tenancy

Filament Studio has built-in multi-tenancy support. All major models are scoped by `tenant_id`, ensuring complete data isolation between tenants.

## How It Works

Every model in Filament Studio includes a `tenant_id` column. Queries automatically filter records to the current tenant using Filament's tenant context.

### Tenant-Scoped Models

| Model | Scope |
|-------|-------|
| `StudioCollection` | Collections belong to a tenant |
| `StudioField` | Fields inherit tenant scope from their collection |
| `StudioRecord` | Records are scoped to tenant |
| `StudioDashboard` | Dashboards are per-tenant |
| `StudioPanel` | Panels inherit tenant scope |
| `StudioApiKey` | API keys are per-tenant |
| `StudioSavedFilter` | Saved filters are per-tenant |
| `StudioRecordVersion` | Version history is per-tenant |
| `StudioMigrationLog` | Audit logs are per-tenant |

### Query Scoping

All models provide a `forTenant(?int $tenantId)` scope:

```php
StudioCollection::forTenant($tenantId)->get();
StudioRecord::forTenant($tenantId)->where(...)->get();
```

When `tenant_id` is `null`, records are treated as global (accessible to all tenants).

## Tenant Lifecycle Hook

The plugin provides a hook for seeding data when a new tenant is created:

```php
FilamentStudioPlugin::make()
    ->afterTenantCreated(function (Model $tenant) {
        // Seed default collections for the new tenant
        CollectionSeeder::seedForTenant($tenant->id, [
            [
                'name' => 'contacts',
                'label' => 'Contacts',
                'fields' => [
                    ['column_name' => 'name', 'label' => 'Name', 'field_type' => 'text'],
                    ['column_name' => 'email', 'label' => 'Email', 'field_type' => 'text'],
                ],
            ],
        ]);
    });
```

## API Tenancy

API keys can be scoped to a tenant. When a tenant-scoped API key is used, all API operations are automatically filtered to that tenant's data. See [REST API](api.md) for details.
