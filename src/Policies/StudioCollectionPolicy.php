<?php

namespace Flexpik\FilamentStudio\Policies;

use Filament\Facades\Filament;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Illuminate\Foundation\Auth\User;

class StudioCollectionPolicy
{
    public function viewAny(User $user): bool
    {
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('studio.viewAny');
        }

        return true;
    }

    public function create(User $user): bool
    {
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('studio.create');
        }

        return true;
    }

    public function update(User $user, StudioCollection $collection): bool
    {
        if (! $this->belongsToCurrentTenant($collection)) {
            return false;
        }

        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('studio.update');
        }

        return true;
    }

    public function delete(User $user, StudioCollection $collection): bool
    {
        if (! $this->belongsToCurrentTenant($collection)) {
            return false;
        }

        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('studio.delete');
        }

        return true;
    }

    public function manageFields(User $user, StudioCollection $collection): bool
    {
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('studio.manageFields');
        }

        return true;
    }

    public function viewRecords(User $user, StudioCollection $collection): bool
    {
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('studio.viewRecords');
        }

        return true;
    }

    public function createRecord(User $user, StudioCollection $collection): bool
    {
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('studio.createRecord');
        }

        return true;
    }

    public function updateRecord(User $user, StudioCollection $collection): bool
    {
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('studio.updateRecord');
        }

        return true;
    }

    public function deleteRecord(User $user, StudioCollection $collection): bool
    {
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('studio.deleteRecord');
        }

        return true;
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
