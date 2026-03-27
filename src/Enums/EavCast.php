<?php

namespace Flexpik\FilamentStudio\Enums;

enum EavCast: string
{
    case Text = 'text';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Datetime = 'datetime';
    case Json = 'json';

    public function column(): string
    {
        return match ($this) {
            self::Text => 'val_text',
            self::Integer => 'val_integer',
            self::Decimal => 'val_decimal',
            self::Boolean => 'val_boolean',
            self::Datetime => 'val_datetime',
            self::Json => 'val_json',
        };
    }
}
