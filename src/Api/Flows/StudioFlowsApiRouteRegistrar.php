<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Flows;

use Flexpik\FilamentStudio\Api\Flows\Controllers\FlowController;
use Flexpik\FilamentStudio\Api\Flows\Controllers\FlowGraphController;
use Flexpik\FilamentStudio\Api\Flows\Controllers\FlowMetaController;
use Flexpik\FilamentStudio\Api\Flows\Controllers\FlowRunBulkExportController;
use Flexpik\FilamentStudio\Api\Flows\Controllers\FlowRunController;
use Flexpik\FilamentStudio\Api\Flows\Controllers\FlowWebhookController;
use Flexpik\FilamentStudio\Api\Middleware\ValidateFlowApiKey;
use Illuminate\Support\Facades\Route;

class StudioFlowsApiRouteRegistrar
{
    public static function register(): void
    {
        $prefix = config('filament-studio.api.prefix', 'api/studio');

        Route::prefix($prefix)
            ->middleware(['api', 'throttle:studio-api', ValidateFlowApiKey::class])
            ->group(function () {
                Route::get('flows/meta/operations', [FlowMetaController::class, 'operations']);
                Route::get('flows/meta/triggers', [FlowMetaController::class, 'triggers']);
                Route::get('flows/meta/collections', [FlowMetaController::class, 'collections']);
                Route::get('flows', [FlowController::class, 'index']);
                Route::post('flows', [FlowController::class, 'store']);
                Route::get('flows/{id}', [FlowController::class, 'show']);
                Route::put('flows/{id}', [FlowController::class, 'update']);
                Route::delete('flows/{id}', [FlowController::class, 'destroy']);
                Route::get('flows/{id}/graph', [FlowGraphController::class, 'show']);
                Route::put('flows/{id}/graph', [FlowGraphController::class, 'save']);
                Route::post('flows/{id}/publish', [FlowGraphController::class, 'publish']);
                Route::get('flows/{id}/versions', [FlowGraphController::class, 'versions']);
                Route::get('flows/{id}/versions/{versionId}', [FlowGraphController::class, 'showVersion']);
                Route::post('flows/{id}/versions/{versionId}/rollback', [FlowGraphController::class, 'rollback']);
                Route::post('flows/{id}/run', [FlowRunController::class, 'run']);
                Route::post('flows/{id}/test', [FlowRunController::class, 'test']);
                Route::get('flows/{id}/runs', [FlowRunController::class, 'index']);
                Route::get('flows/{id}/runs/export', [FlowRunBulkExportController::class, 'export']);
                Route::get('flows/{id}/runs/{runId}', [FlowRunController::class, 'show']);
                Route::get('flows/runs/{runId}/export', [FlowRunController::class, 'export']);
                Route::post('flows/runs/{runId}/cancel', [FlowRunController::class, 'cancel']);
                Route::post('flows/runs/{runId}/replay', [FlowRunController::class, 'replay']);
                Route::post('flows/runs/{runId}/step', [FlowRunController::class, 'step']);
            });

        // Public webhook route (no API key required)
        Route::post($prefix.'/webhooks/{flowSlug}', [FlowWebhookController::class, 'handle'])
            ->middleware(['api', 'throttle:studio-flow-webhook'])
            ->name('studio.flows.webhook');
    }
}
