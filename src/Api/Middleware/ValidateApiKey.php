<?php

namespace Flexpik\FilamentStudio\Api\Middleware;

use Closure;
use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    public function handle(Request $request, Closure $next, string $action): Response
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

        $collectionSlug = $request->route('collection_slug');
        $apiAction = ApiAction::from($action);

        if (! $apiKey->can($collectionSlug, $apiAction)) {
            return response()->json([
                'message' => 'This API key does not have permission to perform this action.',
            ], 403);
        }

        $apiKey->touchLastUsed();
        $request->attributes->set('studio_api_key', $apiKey);

        return $next($request);
    }
}
