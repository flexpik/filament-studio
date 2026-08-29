<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Middleware;

use Closure;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateFlowApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainKey = $request->header('X-Api-Key');

        if (! $plainKey) {
            return response()->json([
                'message' => 'API key required. Provide it via the X-Api-Key header.',
            ], 401);
        }

        $apiKey = StudioApiKey::findByKey($plainKey);

        if (! $apiKey) {
            return response()->json([
                'message' => 'Invalid API key.',
            ], 401);
        }

        if (! $apiKey->canManage(StudioScope::Flows)) {
            return response()->json([
                'message' => 'This API key does not have permission to manage flows.',
            ], 403);
        }

        $apiKey->touchLastUsed();
        $request->attributes->set('studio_api_key', $apiKey);
        $request->attributes->set('studio_api_key_tenant_id', $apiKey->tenant_id);

        return $next($request);
    }
}
