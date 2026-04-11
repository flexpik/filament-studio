<?php

use Flexpik\FilamentStudio\Enums\StudioPermission;
use Flexpik\FilamentStudio\Support\PermissionRegistrar;

it('detects when spatie permission is installed', function () {
    expect(PermissionRegistrar::spatieIsInstalled())->toBeTrue();
});

it('returns all studio permission names', function () {
    $names = PermissionRegistrar::permissionNames();

    expect($names)->toBeArray()
        ->toHaveCount(2)
        ->toBe(StudioPermission::values());
});

it('skips sync gracefully when spatie is not installed', function () {
    // Should not throw
    PermissionRegistrar::sync();
})->throwsNoExceptions();
