<?php

use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Enums\FilterOperator;

it('maps each operator to a sql operator string', function () {
    expect(FilterOperator::Eq->toSql())->toBe('=');
    expect(FilterOperator::Neq->toSql())->toBe('!=');
    expect(FilterOperator::Lt->toSql())->toBe('<');
    expect(FilterOperator::Lte->toSql())->toBe('<=');
    expect(FilterOperator::Gt->toSql())->toBe('>');
    expect(FilterOperator::Gte->toSql())->toBe('>=');
});

it('returns null sql for non-comparison operators', function () {
    expect(FilterOperator::Contains->toSql())->toBeNull();
    expect(FilterOperator::Between->toSql())->toBeNull();
    expect(FilterOperator::IsNull->toSql())->toBeNull();
    expect(FilterOperator::IsTrue->toSql())->toBeNull();
});

it('returns operators valid for text cast', function () {
    $ops = FilterOperator::forCast(EavCast::Text);

    expect($ops)->toContain(FilterOperator::Eq)
        ->toContain(FilterOperator::Contains)
        ->toContain(FilterOperator::StartsWith)
        ->not->toContain(FilterOperator::Gt);
});

it('returns operators valid for integer cast', function () {
    $ops = FilterOperator::forCast(EavCast::Integer);

    expect($ops)->toContain(FilterOperator::Eq)
        ->toContain(FilterOperator::Gt)
        ->toContain(FilterOperator::Between)
        ->not->toContain(FilterOperator::Contains);
});

it('returns operators valid for boolean cast', function () {
    $ops = FilterOperator::forCast(EavCast::Boolean);

    expect($ops)->toContain(FilterOperator::IsTrue)
        ->toContain(FilterOperator::IsFalse)
        ->toContain(FilterOperator::IsNull)
        ->not->toContain(FilterOperator::Gt);
});

it('returns operators valid for datetime cast', function () {
    $ops = FilterOperator::forCast(EavCast::Datetime);

    expect($ops)->toContain(FilterOperator::Eq)
        ->toContain(FilterOperator::Lt)
        ->toContain(FilterOperator::Gt)
        ->toContain(FilterOperator::Between)
        ->not->toContain(FilterOperator::Contains);
});

it('returns operators valid for json cast', function () {
    $ops = FilterOperator::forCast(EavCast::Json);

    expect($ops)->toContain(FilterOperator::ContainsAny)
        ->toContain(FilterOperator::ContainsAll)
        ->toContain(FilterOperator::ContainsNone)
        ->not->toContain(FilterOperator::Gt);
});

it('provides a human-readable label for each operator', function () {
    expect(FilterOperator::Eq->label())->toBe('equals');
    expect(FilterOperator::Contains->label())->toBe('contains');
    expect(FilterOperator::Between->label())->toBe('is between');
});

it('identifies unary operators correctly', function () {
    expect(FilterOperator::IsNull->isUnary())->toBeTrue();
    expect(FilterOperator::IsTrue->isUnary())->toBeTrue();
    expect(FilterOperator::IsEmpty->isUnary())->toBeTrue();
    expect(FilterOperator::Eq->isUnary())->toBeFalse();
    expect(FilterOperator::Contains->isUnary())->toBeFalse();
});

it('identifies range operators correctly', function () {
    expect(FilterOperator::Between->isRange())->toBeTrue();
    expect(FilterOperator::NotBetween->isRange())->toBeTrue();
    expect(FilterOperator::Eq->isRange())->toBeFalse();
});

it('identifies multi-value operators correctly', function () {
    expect(FilterOperator::In->isMultiValue())->toBeTrue();
    expect(FilterOperator::ContainsAny->isMultiValue())->toBeTrue();
    expect(FilterOperator::Eq->isMultiValue())->toBeFalse();
});

it('provides datetime-specific labels', function () {
    $labels = FilterOperator::labelsForCast(EavCast::Datetime);

    expect($labels[FilterOperator::Lt->value])->toBe('before');
    expect($labels[FilterOperator::Gt->value])->toBe('after');
    expect($labels[FilterOperator::Lte->value])->toBe('on or before');
    expect($labels[FilterOperator::Gte->value])->toBe('on or after');
});
