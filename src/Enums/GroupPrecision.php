<?php

namespace Flexpik\FilamentStudio\Enums;

enum GroupPrecision: string
{
    case Hour = 'hour';
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';

    public function label(): string
    {
        return match ($this) {
            self::Hour => 'Hour',
            self::Day => 'Day',
            self::Week => 'Week',
            self::Month => 'Month',
            self::Year => 'Year',
        };
    }

    public function mysqlFormat(): string
    {
        return match ($this) {
            self::Hour => '%Y-%m-%d %H:00',
            self::Day => '%Y-%m-%d',
            self::Week => '%x-W%v',
            self::Month => '%Y-%m',
            self::Year => '%Y',
        };
    }

    public function postgresqlTrunc(): string
    {
        return match ($this) {
            self::Hour => 'hour',
            self::Day => 'day',
            self::Week => 'week',
            self::Month => 'month',
            self::Year => 'year',
        };
    }

    public function sqliteFormat(): string
    {
        return match ($this) {
            self::Hour => '%Y-%m-%d %H:00',
            self::Day => '%Y-%m-%d',
            self::Week => '%Y-W%W',
            self::Month => '%Y-%m',
            self::Year => '%Y',
        };
    }
}
