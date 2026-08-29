<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Services;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;

class AssertCanEnablePublicWebhook
{
    /**
     * Throws AuthorizationException if the actor is not allowed to set webhook_auth_mode=none.
     */
    public function assert(StudioFlow $flow, Authenticatable $actor): void
    {
        if (! method_exists($actor, 'hasRole') || ! $actor->hasRole('studio-admin')) {
            throw new AuthorizationException('Only studio-admin can enable public webhook mode.');
        }
    }
}
