<?php

use Filament\Schemas\Components\Component;
use Flexpik\FilamentStudio\Panels\PanelTypeRegistry;

/**
 * Filament's JS-powered fields (Select, etc.) fail to bind their wire:model
 * state when they only appear after a reactive schema rebuild (e.g. after
 * picking a panel type) and have no explicit default() — Livewire cannot
 * find the property on the component and the typed value never reaches the
 * server (see filamentphp/filament#12561). Every required config field must
 * therefore declare a default(), even if it's just null.
 */
it('gives every required top-level config field an explicit default', function () {
    $registry = app(PanelTypeRegistry::class);

    foreach ($registry->all() as $key => $class) {
        foreach ($class::configSchema() as $component) {
            if (! method_exists($component, 'isRequired') || ! $component->isRequired()) {
                continue;
            }

            $hasDefaultState = (new ReflectionProperty(Component::class, 'hasDefaultState'))
                ->getValue($component);

            expect($hasDefaultState)
                ->toBeTrue("Panel type [{$key}] has a required config field without a default(), which breaks Livewire state hydration when the field appears reactively.");
        }
    }
});
