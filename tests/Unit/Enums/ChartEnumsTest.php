<?php

use Flexpik\FilamentStudio\Enums\CurveType;
use Flexpik\FilamentStudio\Enums\FillType;
use Flexpik\FilamentStudio\Enums\GroupPrecision;

it('has all group precision values', function () {
    expect(GroupPrecision::cases())->toHaveCount(5);
    expect(GroupPrecision::Hour->value)->toBe('hour');
    expect(GroupPrecision::Day->value)->toBe('day');
    expect(GroupPrecision::Week->value)->toBe('week');
    expect(GroupPrecision::Month->value)->toBe('month');
    expect(GroupPrecision::Year->value)->toBe('year');
});

it('returns MySQL DATE_FORMAT string for group precision', function () {
    expect(GroupPrecision::Day->mysqlFormat())->toBe('%Y-%m-%d');
    expect(GroupPrecision::Month->mysqlFormat())->toBe('%Y-%m');
    expect(GroupPrecision::Year->mysqlFormat())->toBe('%Y');
    expect(GroupPrecision::Hour->mysqlFormat())->toBe('%Y-%m-%d %H:00');
    expect(GroupPrecision::Week->mysqlFormat())->toBe('%x-W%v');
});

it('has curve type cases', function () {
    expect(CurveType::cases())->toHaveCount(3);
    expect(CurveType::Smooth->value)->toBe('smooth');
});

it('has fill type cases', function () {
    expect(FillType::cases())->toHaveCount(3);
    expect(FillType::Gradient->value)->toBe('gradient');
});
