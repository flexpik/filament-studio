<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Facades;

use Flexpik\FilamentStudio\Support\FilamentStudioManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void registerFlowOperation(string $key, string $label, string $activity, ?string $configSchema = null, ?string $icon = null, string $group = 'general', bool $dangerous = false)
 * @method static void registerFlowTrigger(string $key, string $label, string $handler, ?string $configSchema = null, ?string $icon = null)
 */
class FilamentStudio extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FilamentStudioManager::class;
    }
}
