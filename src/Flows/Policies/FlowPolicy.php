<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Policies;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Illuminate\Foundation\Auth\User;

class FlowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_flows');
    }

    public function view(User $user, StudioFlow $flow): bool
    {
        return $user->can('view_flows');
    }

    public function create(User $user): bool
    {
        return $user->can('create_flows');
    }

    public function update(User $user, StudioFlow $flow): bool
    {
        return $user->can('update_flows');
    }

    public function delete(User $user, StudioFlow $flow): bool
    {
        return $user->can('delete_flows');
    }

    public function run(User $user, StudioFlow $flow): bool
    {
        return $user->can('run_flows');
    }
}
