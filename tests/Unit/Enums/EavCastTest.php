<?php

use Flexpik\FilamentStudio\Enums\EavCast;

it('has the correct cases', function () {
    expect(EavCast::cases())->toHaveCount(6);
    expect(EavCast::Text->value)->toBe('text');
    expect(EavCast::Integer->value)->toBe('integer');
    expect(EavCast::Decimal->value)->toBe('decimal');
    expect(EavCast::Boolean->value)->toBe('boolean');
    expect(EavCast::Datetime->value)->toBe('datetime');
    expect(EavCast::Json->value)->toBe('json');
});

it('maps each cast to a val_* column name', function () {
    expect(EavCast::Text->column())->toBe('val_text');
    expect(EavCast::Integer->column())->toBe('val_integer');
    expect(EavCast::Decimal->column())->toBe('val_decimal');
    expect(EavCast::Boolean->column())->toBe('val_boolean');
    expect(EavCast::Datetime->column())->toBe('val_datetime');
    expect(EavCast::Json->column())->toBe('val_json');
});
