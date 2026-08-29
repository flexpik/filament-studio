<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Services;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

class RotateWebhookSecret
{
    public function __construct(private WriteFlowAuditLog $auditLog) {}

    /**
     * Generate a new webhook secret, persist it, write audit log.
     * Returns the NEW plain-text secret (for one-time display).
     */
    public function rotate(StudioFlow $flow, ?Authenticatable $actor = null): string
    {
        $oldSecret = $flow->webhook_secret; // encrypted value, readable via cast
        $oldPrefix = $oldSecret ? substr((string) $oldSecret, 0, 4) : '';

        $newSecret = Str::random(48);
        $flow->forceFill(['webhook_secret' => $newSecret])->save();

        $this->auditLog->write($flow, 'webhook_secret_rotated', [
            'previous_secret_prefix' => $oldPrefix,
        ], $actor);

        return $newSecret;
    }
}
