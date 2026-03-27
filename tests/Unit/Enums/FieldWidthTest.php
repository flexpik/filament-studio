<?php

use Flexpik\FilamentStudio\Enums\FieldWidth;

it('has the correct cases', function () {
    expect(FieldWidth::cases())->toHaveCount(3);
    expect(FieldWidth::Half->value)->toBe('half');
    expect(FieldWidth::Full->value)->toBe('full');
    expect(FieldWidth::Expanded->value)->toBe('expanded');
});
