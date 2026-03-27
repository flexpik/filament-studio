<?php

use Carbon\Carbon;
use Filament\Facades\Filament;
use Flexpik\FilamentStudio\Filtering\DynamicValueResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-03-14 12:00:00'));
});

it('resolves $CURRENT_USER to the authenticated user id', function () {
    Auth::shouldReceive('id')->andReturn(42);

    $resolved = DynamicValueResolver::resolve('$CURRENT_USER');

    expect($resolved)->toBe(42);
});

it('resolves $NOW to current datetime string', function () {
    $resolved = DynamicValueResolver::resolve('$NOW');

    expect($resolved)->toBe('2026-03-14 12:00:00');
});

it('resolves $NOW with negative adjustment', function () {
    $resolved = DynamicValueResolver::resolve('$NOW(-7 days)');

    expect($resolved)->toBe('2026-03-07 12:00:00');
});

it('resolves $NOW with positive adjustment', function () {
    $resolved = DynamicValueResolver::resolve('$NOW(+1 month)');

    expect($resolved)->toBe('2026-04-14 12:00:00');
});

it('returns non-dynamic values unchanged', function () {
    expect(DynamicValueResolver::resolve('published'))->toBe('published');
    expect(DynamicValueResolver::resolve(42))->toBe(42);
    expect(DynamicValueResolver::resolve(null))->toBeNull();
});

it('detects dynamic value tokens', function () {
    expect(DynamicValueResolver::isDynamic('$CURRENT_USER'))->toBeTrue();
    expect(DynamicValueResolver::isDynamic('$NOW'))->toBeTrue();
    expect(DynamicValueResolver::isDynamic('$NOW(-7 days)'))->toBeTrue();
    expect(DynamicValueResolver::isDynamic('published'))->toBeFalse();
    expect(DynamicValueResolver::isDynamic(42))->toBeFalse();
});

it('resolves $CURRENT_USER to null when unauthenticated', function () {
    Auth::shouldReceive('id')->andReturn(null);

    $resolved = DynamicValueResolver::resolve('$CURRENT_USER');

    expect($resolved)->toBeNull();
});

it('isDynamic with empty string returns false', function () {
    expect(DynamicValueResolver::isDynamic(''))->toBeFalse();
});

it('isDynamic with null returns false', function () {
    expect(DynamicValueResolver::isDynamic(null))->toBeFalse();
});

it('isDynamic with array returns false', function () {
    expect(DynamicValueResolver::isDynamic(['$CURRENT_USER']))->toBeFalse();
});

it('resolves $CURRENT_TENANT to tenant key', function () {
    $tenant = Mockery::mock(Model::class);
    $tenant->shouldReceive('getKey')->andReturn(99);
    Filament::shouldReceive('getTenant')->andReturn($tenant);

    $resolved = DynamicValueResolver::resolve('$CURRENT_TENANT');

    expect($resolved)->toBe(99);
});

it('resolves $CURRENT_TENANT to null when no tenant', function () {
    Filament::shouldReceive('getTenant')->andReturn(null);

    $resolved = DynamicValueResolver::resolve('$CURRENT_TENANT');

    expect($resolved)->toBeNull();
});
