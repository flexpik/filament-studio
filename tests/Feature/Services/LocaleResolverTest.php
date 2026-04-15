<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Services\LocaleResolver;
use Illuminate\Http\Request;

beforeEach(function () {
    config(['filament-studio.locales.enabled' => true]);
    config(['filament-studio.locales.available' => ['en', 'fr', 'de']]);
    config(['filament-studio.locales.default' => 'en']);
});

it('returns default locale when multilingual is disabled', function () {
    config(['filament-studio.locales.enabled' => false]);

    $resolver = new LocaleResolver;

    expect($resolver->resolve())->toBe('en');
});

it('resolves locale from query parameter', function () {
    $request = Request::create('/test', 'GET', ['locale' => 'fr']);
    app()->instance('request', $request);

    $resolver = new LocaleResolver;

    expect($resolver->resolve())->toBe('fr');
});

it('resolves locale from X-Locale header', function () {
    $request = Request::create('/test', 'GET', [], [], [], ['HTTP_X_LOCALE' => 'de']);
    app()->instance('request', $request);

    $resolver = new LocaleResolver;

    expect($resolver->resolve())->toBe('de');
});

it('prefers query parameter over header', function () {
    $request = Request::create('/test', 'GET', ['locale' => 'fr'], [], [], ['HTTP_X_LOCALE' => 'de']);
    app()->instance('request', $request);

    $resolver = new LocaleResolver;

    expect($resolver->resolve())->toBe('fr');
});

it('falls back to default for invalid locale', function () {
    $request = Request::create('/test', 'GET', ['locale' => 'xx']);
    app()->instance('request', $request);

    $resolver = new LocaleResolver;

    expect($resolver->resolve())->toBe('en');
});

it('resolves locale from session', function () {
    session(['studio_locale' => 'de']);

    $resolver = new LocaleResolver;

    expect($resolver->resolve())->toBe('de');
});

it('scopes to collection supported locales', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr'],
    ]);

    $request = Request::create('/test', 'GET', ['locale' => 'de']);
    app()->instance('request', $request);

    $resolver = new LocaleResolver;

    // 'de' is in global pool but not in collection's supported_locales
    expect($resolver->resolve($collection))->toBe('en');
});

it('uses collection default_locale when set', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr'],
        'default_locale' => 'fr',
    ]);

    $resolver = new LocaleResolver;

    expect($resolver->resolve($collection))->toBe('fr');
});

it('returns available locales for a collection', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr'],
    ]);

    $resolver = new LocaleResolver;

    expect($resolver->availableLocales($collection))->toBe(['en', 'fr']);
});

it('returns global locales when collection has no override', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => null,
    ]);

    $resolver = new LocaleResolver;

    expect($resolver->availableLocales($collection))->toBe(['en', 'fr', 'de']);
});

it('returns default locale for collection', function () {
    $collection = StudioCollection::factory()->create([
        'default_locale' => 'fr',
    ]);

    $resolver = new LocaleResolver;

    expect($resolver->defaultLocale($collection))->toBe('fr');
});

it('falls back to global default when collection has no default', function () {
    $collection = StudioCollection::factory()->create([
        'default_locale' => null,
    ]);

    $resolver = new LocaleResolver;

    expect($resolver->defaultLocale($collection))->toBe('en');
});

it('checks if multilingual is enabled globally', function () {
    expect((new LocaleResolver)->isEnabled())->toBeTrue();

    config(['filament-studio.locales.enabled' => false]);

    expect((new LocaleResolver)->isEnabled())->toBeFalse();
});

it('ignores session locale when not in collection supported locales', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr'],
    ]);

    session(['studio_locale' => 'de']);

    $resolver = new LocaleResolver;

    expect($resolver->resolve($collection))->toBe('en');
});

it('prefers query parameter over session', function () {
    session(['studio_locale' => 'de']);

    $request = Request::create('/test', 'GET', ['locale' => 'fr']);
    app()->instance('request', $request);

    $resolver = new LocaleResolver;

    expect($resolver->resolve())->toBe('fr');
});

it('prefers header over session', function () {
    session(['studio_locale' => 'fr']);

    $request = Request::create('/test', 'GET', [], [], [], ['HTTP_X_LOCALE' => 'de']);
    app()->instance('request', $request);

    $resolver = new LocaleResolver;

    expect($resolver->resolve())->toBe('de');
});

it('returns global available locales when collection is null', function () {
    $resolver = new LocaleResolver;

    expect($resolver->availableLocales(null))->toBe(['en', 'fr', 'de']);
});

it('falls back to global locales when collection has empty supported_locales array', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => [],
    ]);

    $resolver = new LocaleResolver;

    expect($resolver->availableLocales($collection))->toBe(['en', 'fr', 'de']);
});

it('does not check request sources when disabled', function () {
    config(['filament-studio.locales.enabled' => false]);

    // Set session locale to 'fr' — should be ignored when disabled
    session(['studio_locale' => 'fr']);

    $request = Request::create('/test', 'GET', ['locale' => 'fr']);
    app()->instance('request', $request);

    $resolver = new LocaleResolver;

    // Should return default 'en', not 'fr' from query/session
    expect($resolver->resolve())->toBe('en');
});

it('falls back to default when header is present but not in available locales', function () {
    $request = Request::create('/test', 'GET', [], [], [], ['HTTP_X_LOCALE' => 'xx']);
    app()->instance('request', $request);

    $resolver = new LocaleResolver;

    expect($resolver->resolve())->toBe('en');
});
