<?php

use Flexpik\FilamentStudio\Enums\SortDirection;

it('has the correct cases', function () {
    expect(SortDirection::cases())->toHaveCount(2);
    expect(SortDirection::Asc->value)->toBe('asc');
    expect(SortDirection::Desc->value)->toBe('desc');
});
