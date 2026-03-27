# Authorization

Filament Studio uses Laravel's policy system for access control. Two policies govern permissions: one for collections and records, and one for API keys.

## Collection Policy

`StudioCollectionPolicy` controls access to collections and their records. It checks for specific Gate permissions on the authenticated user.

### Permissions

| Gate | Controls |
|------|----------|
| `studio.viewAny` | View the schema manager and collection list |
| `studio.create` | Create new collections |
| `studio.update` | Edit collection settings and fields |
| `studio.delete` | Delete collections |
| `studio.manageFields` | Add, edit, or remove fields on a collection |
| `studio.viewRecords` | View records in a collection |
| `studio.createRecord` | Create new records |
| `studio.updateRecord` | Edit existing records |
| `studio.deleteRecord` | Delete records |

### Tenant Scoping

For tenant-scoped operations (`update`, `delete`, `manageFields`, record operations), the policy verifies the collection belongs to the user's current tenant.

### Default Behavior

If the authenticated user model does not implement permission checking (i.e., does not use a package like Spatie Permission), all gates default to `true`. This means the policy is permissive by default — it only restricts access when explicit permission checking is in place.

## API Key Policy

`StudioApiKeyPolicy` controls access to the API key management interface.

| Gate | Controls |
|------|----------|
| `studio.manageApiKeys` | Full CRUD on API keys |

API key operations (`view`, `update`, `delete`) are also tenant-scoped — users can only manage keys belonging to their tenant.

## Integrating with Spatie Permission

If you use [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission), create the permissions and assign them to roles:

```php
// Create permissions
Permission::create(['name' => 'studio.viewAny']);
Permission::create(['name' => 'studio.create']);
Permission::create(['name' => 'studio.update']);
Permission::create(['name' => 'studio.delete']);
Permission::create(['name' => 'studio.manageFields']);
Permission::create(['name' => 'studio.viewRecords']);
Permission::create(['name' => 'studio.createRecord']);
Permission::create(['name' => 'studio.updateRecord']);
Permission::create(['name' => 'studio.deleteRecord']);
Permission::create(['name' => 'studio.manageApiKeys']);

// Assign to a role
$admin = Role::findByName('admin');
$admin->givePermissionTo([
    'studio.viewAny',
    'studio.create',
    'studio.update',
    'studio.delete',
    'studio.manageFields',
    'studio.viewRecords',
    'studio.createRecord',
    'studio.updateRecord',
    'studio.deleteRecord',
    'studio.manageApiKeys',
]);

// Read-only role
$viewer = Role::findByName('viewer');
$viewer->givePermissionTo([
    'studio.viewAny',
    'studio.viewRecords',
]);
```

## Custom Authorization

You can override the policies entirely by binding your own implementations in a service provider:

```php
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioApiKey;

Gate::policy(StudioCollection::class, YourCustomCollectionPolicy::class);
Gate::policy(StudioApiKey::class, YourCustomApiKeyPolicy::class);
```
