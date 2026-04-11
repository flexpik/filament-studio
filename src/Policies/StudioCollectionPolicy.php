<?php

namespace Flexpik\FilamentStudio\Policies;

use Filament\Facades\Filament;
use Flexpik\FilamentStudio\Enums\StudioPermission;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Illuminate\Foundation\Auth\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class StudioCollectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'ViewAny', 'StudioCollection');
    }

    public function view(User $user, StudioCollection $collection): bool
    {
        if (! $this->belongsToCurrentTenant($collection)) {
            return false;
        }

        return $this->checkPermission($user, 'View', 'StudioCollection');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'Create', 'StudioCollection');
    }

    public function update(User $user, StudioCollection $collection): bool
    {
        if (! $this->belongsToCurrentTenant($collection)) {
            return false;
        }

        return $this->checkPermission($user, 'Update', 'StudioCollection');
    }

    public function delete(User $user, StudioCollection $collection): bool
    {
        if (! $this->belongsToCurrentTenant($collection)) {
            return false;
        }

        return $this->checkPermission($user, 'Delete', 'StudioCollection');
    }

    public function manageFields(User $user, StudioCollection $collection): bool
    {
        if (method_exists($user, 'hasPermissionTo')) {
            return $this->safeCheckPermission($user, StudioPermission::ManageFields->value);
        }

        return true;
    }

    public function viewRecords(User $user, StudioCollection $collection): bool
    {
        return $this->hasCollectionPermission($user, $collection, 'viewRecords');
    }

    public function createRecord(User $user, StudioCollection $collection): bool
    {
        return $this->hasCollectionPermission($user, $collection, 'createRecord');
    }

    public function updateRecord(User $user, StudioCollection $collection): bool
    {
        return $this->hasCollectionPermission($user, $collection, 'updateRecord');
    }

    public function deleteRecord(User $user, StudioCollection $collection): bool
    {
        return $this->hasCollectionPermission($user, $collection, 'deleteRecord');
    }

    protected function checkPermission(User $user, string $action, string $model): bool
    {
        if (! method_exists($user, 'hasPermissionTo')) {
            return true;
        }

        $separator = config('filament-shield.permissions.separator', ':');

        return $this->safeCheckPermission($user, "{$action}{$separator}{$model}");
    }

    protected function hasCollectionPermission(User $user, StudioCollection $collection, string $action): bool
    {
        if (! method_exists($user, 'hasPermissionTo')) {
            return true;
        }

        return $this->safeCheckPermission($user, "studio.collection.{$collection->slug}.{$action}");
    }

    protected function safeCheckPermission(User $user, string $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    protected function belongsToCurrentTenant(StudioCollection $collection): bool
    {
        $tenantId = Filament::getTenant()?->getKey();

        if ($tenantId === null) {
            return true;
        }

        return $collection->tenant_id === $tenantId;
    }
}
