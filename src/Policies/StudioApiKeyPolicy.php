<?php

namespace Flexpik\FilamentStudio\Policies;

use Filament\Facades\Filament;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Foundation\Auth\User;

class StudioApiKeyPolicy
{
    public function viewAny(User $user): bool
    {
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('studio.manageApiKeys');
        }

        return true;
    }

    public function view(User $user, StudioApiKey $apiKey): bool
    {
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('studio.manageApiKeys');
        }

        return true;
    }

    public function create(User $user): bool
    {
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('studio.manageApiKeys');
        }

        return true;
    }

    public function update(User $user, StudioApiKey $apiKey): bool
    {
        if (! $this->belongsToCurrentTenant($apiKey)) {
            return false;
        }

        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('studio.manageApiKeys');
        }

        return true;
    }

    public function delete(User $user, StudioApiKey $apiKey): bool
    {
        if (! $this->belongsToCurrentTenant($apiKey)) {
            return false;
        }

        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('studio.manageApiKeys');
        }

        return true;
    }

    protected function belongsToCurrentTenant(StudioApiKey $apiKey): bool
    {
        $tenantId = Filament::getTenant()?->getKey();

        if ($tenantId === null) {
            return true;
        }

        return $apiKey->tenant_id === $tenantId;
    }
}
