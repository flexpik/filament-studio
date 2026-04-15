<?php

namespace Flexpik\FilamentStudio\Services;

use Flexpik\FilamentStudio\Models\StudioCollection;

class LocaleResolver
{
    public function isEnabled(): bool
    {
        return (bool) config('filament-studio.locales.enabled', false);
    }

    /**
     * Resolve the active locale.
     * Priority: query param > header > session > collection default > global default.
     */
    public function resolve(?StudioCollection $collection = null): string
    {
        $default = $this->defaultLocale($collection);

        if (! $this->isEnabled()) {
            return $default;
        }

        $available = $this->availableLocales($collection);

        $request = request();

        // 1. Query parameter
        $locale = $request->query('locale');
        if ($locale && in_array($locale, $available, true)) {
            return $locale;
        }

        // 2. X-Locale header
        $locale = $request->header('X-Locale');
        if ($locale && in_array($locale, $available, true)) {
            return $locale;
        }

        // 3. Session
        $locale = session('studio_locale');
        if ($locale && in_array($locale, $available, true)) {
            return $locale;
        }

        return $default;
    }

    /**
     * Get available locales for a collection, falling back to global config.
     *
     * @return array<string>
     */
    public function availableLocales(?StudioCollection $collection = null): array
    {
        if ($collection && ! empty($collection->supported_locales)) {
            return $collection->supported_locales;
        }

        return config('filament-studio.locales.available', ['en']);
    }

    /**
     * Get the default locale for a collection, falling back to global config.
     */
    public function defaultLocale(?StudioCollection $collection = null): string
    {
        if ($collection && $collection->default_locale) {
            return $collection->default_locale;
        }

        return config('filament-studio.locales.default', 'en');
    }
}
