<?php

namespace Flexpik\FilamentStudio\Support;

use Flexpik\FilamentStudio\Enums\StudioPermission;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Spatie\Permission\Models\Permission;

class PermissionRegistrar
{
    /**
     * Check if spatie/laravel-permission is installed by looking for the Permission model.
     */
    public static function spatieIsInstalled(): bool
    {
        return class_exists(Permission::class);
    }

    /**
     * Get all studio permission names.
     *
     * @return array<int, string>
     */
    public static function permissionNames(): array
    {
        return StudioPermission::values();
    }

    /**
     * Sync studio permissions into Spatie's permissions table.
     * Idempotent — safe to call multiple times.
     * No-op if spatie/laravel-permission is not installed.
     */
    public static function sync(?string $guardName = null): void
    {
        if (! static::spatieIsInstalled()) {
            return;
        }

        try {
            $guardName ??= config('filament-studio.permissions.guard');

            $permissionClass = config('permission.models.permission', Permission::class);

            foreach (static::permissionNames() as $name) {
                $attributes = ['name' => $name];

                if ($guardName !== null) {
                    $attributes['guard_name'] = $guardName;
                }

                $permissionClass::firstOrCreate($attributes);
            }

            // Reset cached permissions so newly created ones are available
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable) {
            // Database may not be available (e.g., during artisan package:discover,
            // migrations not yet run, or connection unavailable). Fail silently —
            // permissions will be synced on the next request when the database is ready.
        }
    }

    /**
     * Sync per-collection permissions for ALL existing collections.
     * Removes orphaned permissions for collections that no longer exist.
     * Idempotent — safe to call multiple times.
     * No-op if spatie/laravel-permission is not installed.
     */
    public static function syncCollectionPermissions(?string $guardName = null): void
    {
        if (! static::spatieIsInstalled()) {
            return;
        }

        try {
            $guardName ??= config('filament-studio.permissions.guard');

            $permissionClass = config('permission.models.permission', Permission::class);

            $slugs = StudioCollection::pluck('slug');

            // Build the full set of expected permission names
            $expected = [];
            foreach ($slugs as $slug) {
                foreach (StudioPermission::forCollection($slug) as $name) {
                    $expected[] = $name;
                }
            }

            // Create missing permissions
            foreach ($expected as $name) {
                $attributes = ['name' => $name];

                if ($guardName !== null) {
                    $attributes['guard_name'] = $guardName;
                }

                $permissionClass::firstOrCreate($attributes);
            }

            // Remove orphaned collection permissions (those not in $expected)
            $permissionClass::where('name', 'like', 'studio.collection.%')
                ->whereNotIn('name', $expected)
                ->delete();

            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable) {
            // Fail silently if database is unavailable.
        }
    }

    /**
     * Sync permissions for a single collection.
     * Idempotent — safe to call multiple times.
     * No-op if spatie/laravel-permission is not installed.
     */
    public static function syncForCollection(StudioCollection $collection, ?string $guardName = null): void
    {
        if (! static::spatieIsInstalled()) {
            return;
        }

        try {
            $guardName ??= config('filament-studio.permissions.guard');

            $permissionClass = config('permission.models.permission', Permission::class);

            foreach (StudioPermission::forCollection($collection->slug) as $name) {
                $attributes = ['name' => $name];

                if ($guardName !== null) {
                    $attributes['guard_name'] = $guardName;
                }

                $permissionClass::firstOrCreate($attributes);
            }

            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable) {
            // Fail silently if database is unavailable.
        }
    }

    /**
     * Remove all permissions for a single collection.
     * No-op if spatie/laravel-permission is not installed.
     */
    public static function removeForCollection(StudioCollection $collection, ?string $guardName = null): void
    {
        if (! static::spatieIsInstalled()) {
            return;
        }

        try {
            $permissionClass = config('permission.models.permission', Permission::class);

            $names = StudioPermission::forCollection($collection->slug);

            $query = $permissionClass::whereIn('name', $names);

            if ($guardName !== null) {
                $query->where('guard_name', $guardName);
            }

            $query->delete();

            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable) {
            // Fail silently if database is unavailable.
        }
    }
}
