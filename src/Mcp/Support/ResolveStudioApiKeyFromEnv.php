<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Support;

use Flexpik\FilamentStudio\Models\StudioApiKey;
use RuntimeException;

class ResolveStudioApiKeyFromEnv
{
    public function __construct(protected StudioApiKeyContext $context) {}

    public function resolve(): void
    {
        $value = getenv('STUDIO_API_KEY');

        if ($value === false || $value === '') {
            throw new RuntimeException('STUDIO_API_KEY environment variable is not set.');
        }

        $key = StudioApiKey::query()->where('key', $value)->first();

        if (! $key) {
            throw new RuntimeException('STUDIO_API_KEY is invalid.');
        }

        if (! $key->is_active) {
            throw new RuntimeException('STUDIO_API_KEY is inactive.');
        }

        if ($key->expires_at !== null && $key->expires_at->isPast()) {
            throw new RuntimeException('STUDIO_API_KEY has expired.');
        }

        $this->context->set($key);

        $key->forceFill(['last_used_at' => now()])->save();
    }
}
