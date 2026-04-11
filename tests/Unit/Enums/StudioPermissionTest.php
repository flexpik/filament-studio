<?php

use Flexpik\FilamentStudio\Enums\StudioPermission;

it('has exactly 2 permission cases', function () {
    expect(StudioPermission::cases())->toHaveCount(2);
});

it('has correct string values for all cases', function () {
    expect(StudioPermission::ManageFields->value)->toBe('studio.manageFields')
        ->and(StudioPermission::ManageApiKeys->value)->toBe('studio.manageApiKeys');
});

it('returns all permission values as flat array', function () {
    $all = StudioPermission::values();

    expect($all)->toBeArray()
        ->toHaveCount(2)
        ->toContain('studio.manageFields', 'studio.manageApiKeys');
});
