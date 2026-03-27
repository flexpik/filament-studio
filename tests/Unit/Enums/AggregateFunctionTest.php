<?php

use Flexpik\FilamentStudio\Enums\AggregateFunction;
use Flexpik\FilamentStudio\Enums\EavCast;

it('has all expected aggregate functions', function () {
    expect(AggregateFunction::cases())->toHaveCount(8);
});

it('returns a human-readable label', function () {
    expect(AggregateFunction::Count->label())->toBe('Count');
    expect(AggregateFunction::CountDistinct->label())->toBe('Count (Distinct)');
    expect(AggregateFunction::Avg->label())->toBe('Average');
    expect(AggregateFunction::Sum->label())->toBe('Sum');
});

it('returns aggregate functions compatible with text cast', function () {
    $fns = AggregateFunction::forCast(EavCast::Text);

    expect($fns)->toContain(AggregateFunction::Count)
        ->toContain(AggregateFunction::CountDistinct)
        ->not->toContain(AggregateFunction::Avg)
        ->not->toContain(AggregateFunction::Sum)
        ->not->toContain(AggregateFunction::Min);
});

it('returns aggregate functions compatible with integer cast', function () {
    $fns = AggregateFunction::forCast(EavCast::Integer);

    expect($fns)->toContain(AggregateFunction::Count)
        ->toContain(AggregateFunction::Avg)
        ->toContain(AggregateFunction::Sum)
        ->toContain(AggregateFunction::Min)
        ->toContain(AggregateFunction::Max);
});

it('returns aggregate functions compatible with boolean cast', function () {
    $fns = AggregateFunction::forCast(EavCast::Boolean);

    expect($fns)->toContain(AggregateFunction::Count)
        ->toContain(AggregateFunction::CountDistinct)
        ->not->toContain(AggregateFunction::Avg)
        ->not->toContain(AggregateFunction::Sum);
});

it('identifies which functions need a field', function () {
    expect(AggregateFunction::Count->requiresField())->toBeFalse();
    expect(AggregateFunction::Sum->requiresField())->toBeTrue();
    expect(AggregateFunction::Avg->requiresField())->toBeTrue();
});

it('returns SQL expression fragment', function () {
    expect(AggregateFunction::Count->toSql('val_integer'))->toBe('COUNT(val_integer)');
    expect(AggregateFunction::CountDistinct->toSql('val_text'))->toBe('COUNT(DISTINCT val_text)');
    expect(AggregateFunction::Avg->toSql('val_decimal'))->toBe('AVG(val_decimal)');
});
