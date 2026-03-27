<?php

namespace Flexpik\FilamentStudio\Api;

use Flexpik\FilamentStudio\Api\Middleware\ValidateApiKey;
use Illuminate\Support\Facades\Route;

class StudioApiRouteRegistrar
{
    public static function register(): void
    {
        $prefix = config('filament-studio.api.prefix', 'api/studio');

        Route::prefix($prefix)
            ->middleware(['api', 'throttle:studio-api'])
            ->group(function () {
                Route::get('{collection_slug}', [StudioApiController::class, 'index'])
                    ->middleware(ValidateApiKey::class.':index');

                Route::get('{collection_slug}/{uuid}', [StudioApiController::class, 'show'])
                    ->middleware(ValidateApiKey::class.':show');

                Route::post('{collection_slug}', [StudioApiController::class, 'store'])
                    ->middleware(ValidateApiKey::class.':store');

                Route::put('{collection_slug}/{uuid}', [StudioApiController::class, 'update'])
                    ->middleware(ValidateApiKey::class.':update');

                Route::delete('{collection_slug}/{uuid}', [StudioApiController::class, 'destroy'])
                    ->middleware(ValidateApiKey::class.':destroy');
            });
    }
}
