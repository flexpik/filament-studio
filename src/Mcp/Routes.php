<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\StudioMcpServer;
use Flexpik\FilamentStudio\Mcp\Support\ResolveStudioApiKey;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Route::middleware(['api', 'throttle:studio-mcp', ResolveStudioApiKey::class])
    ->group(function () {
        Mcp::web('/'.config('filament-studio.mcp.http.prefix'), StudioMcpServer::class);
    });
