<?php

namespace Flexpik\FilamentStudio\Policies;

use Flexpik\FilamentStudio\Models\StudioDashboard;
use Illuminate\Foundation\Auth\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class StudioDashboardPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'ViewAny', 'StudioDashboard');
    }

    public function view(User $user, StudioDashboard $dashboard): bool
    {
        return $this->checkPermission($user, 'View', 'StudioDashboard');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'Create', 'StudioDashboard');
    }

    public function update(User $user, StudioDashboard $dashboard): bool
    {
        return $this->checkPermission($user, 'Update', 'StudioDashboard');
    }

    public function delete(User $user, StudioDashboard $dashboard): bool
    {
        return $this->checkPermission($user, 'Delete', 'StudioDashboard');
    }

    protected function checkPermission(User $user, string $action, string $model): bool
    {
        if (! method_exists($user, 'hasPermissionTo')) {
            return true;
        }

        $separator = config('filament-shield.permissions.separator', ':');

        try {
            return $user->hasPermissionTo("{$action}{$separator}{$model}");
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
