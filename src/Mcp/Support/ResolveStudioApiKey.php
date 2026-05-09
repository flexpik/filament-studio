<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Support;

use Closure;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveStudioApiKey
{
    public function __construct(protected StudioApiKeyContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $headerValue = $request->header('X-Api-Key');

        if (! $headerValue) {
            return $this->unauthenticated('API key is missing. Send an X-Api-Key header.');
        }

        $key = StudioApiKey::query()->where('key', $headerValue)->first();

        if (! $key) {
            return $this->unauthenticated('API key is invalid.');
        }

        if (! $key->is_active) {
            return $this->unauthenticated('API key is inactive.');
        }

        if ($key->expires_at !== null && $key->expires_at->isPast()) {
            return $this->unauthenticated('API key has expired.');
        }

        $this->context->set($key);

        $key->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }

    protected function unauthenticated(string $message): JsonResponse
    {
        return new JsonResponse([
            'error' => [
                'code' => 'STUDIO_UNAUTHENTICATED',
                'message' => $message,
            ],
        ], 401);
    }
}
