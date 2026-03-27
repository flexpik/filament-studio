<?php

namespace Flexpik\FilamentStudio\Enums;

enum FilterOperator: string
{
    // Universal
    case Eq = 'eq';
    case Neq = 'neq';
    case IsNull = 'is_null';
    case IsNotNull = 'is_not_null';

    // Text
    case Contains = 'contains';
    case NotContains = 'not_contains';
    case StartsWith = 'starts_with';
    case EndsWith = 'ends_with';
    case IsEmpty = 'is_empty';
    case IsNotEmpty = 'is_not_empty';
    case In = 'in';
    case NotIn = 'not_in';

    // Numeric / Datetime shared
    case Lt = 'lt';
    case Lte = 'lte';
    case Gt = 'gt';
    case Gte = 'gte';
    case Between = 'between';
    case NotBetween = 'not_between';

    // Boolean
    case IsTrue = 'is_true';
    case IsFalse = 'is_false';

    // JSON (multi-select, tags, belongs_to_many)
    case ContainsAny = 'contains_any';
    case ContainsAll = 'contains_all';
    case ContainsNone = 'contains_none';

    /**
     * SQL operator for basic comparison operators.
     * Non-comparison operators return null — they are handled by dedicated query methods.
     */
    public function toSql(): ?string
    {
        return match ($this) {
            self::Eq => '=',
            self::Neq => '!=',
            self::Lt => '<',
            self::Lte => '<=',
            self::Gt => '>',
            self::Gte => '>=',
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Eq => 'equals',
            self::Neq => 'does not equal',
            self::Lt => 'less than',
            self::Lte => 'less than or equal',
            self::Gt => 'greater than',
            self::Gte => 'greater than or equal',
            self::Contains => 'contains',
            self::NotContains => 'does not contain',
            self::StartsWith => 'starts with',
            self::EndsWith => 'ends with',
            self::IsEmpty => 'is empty',
            self::IsNotEmpty => 'is not empty',
            self::In => 'is any of',
            self::NotIn => 'is none of',
            self::Between => 'is between',
            self::NotBetween => 'is not between',
            self::IsNull => 'is null',
            self::IsNotNull => 'is not null',
            self::IsTrue => 'is true',
            self::IsFalse => 'is false',
            self::ContainsAny => 'contains any of',
            self::ContainsAll => 'contains all of',
            self::ContainsNone => 'contains none of',
        };
    }

    /**
     * Return cast-specific labels (e.g., "before"/"after" for datetime instead of "less than"/"greater than").
     *
     * @return array<string, string>
     */
    public static function labelsForCast(EavCast $cast): array
    {
        $operators = self::forCast($cast);
        $labels = [];

        foreach ($operators as $op) {
            $labels[$op->value] = match (true) {
                $cast === EavCast::Datetime && $op === self::Lt => 'before',
                $cast === EavCast::Datetime && $op === self::Lte => 'on or before',
                $cast === EavCast::Datetime && $op === self::Gt => 'after',
                $cast === EavCast::Datetime && $op === self::Gte => 'on or after',
                default => $op->label(),
            };
        }

        return $labels;
    }

    /**
     * Return the subset of operators valid for a given EAV cast type.
     *
     * @return array<FilterOperator>
     */
    public static function forCast(EavCast $cast): array
    {
        return match ($cast) {
            EavCast::Text => [
                self::Eq, self::Neq,
                self::Contains, self::NotContains,
                self::StartsWith, self::EndsWith,
                self::IsEmpty, self::IsNotEmpty,
                self::In, self::NotIn,
                self::IsNull, self::IsNotNull,
            ],
            EavCast::Integer, EavCast::Decimal => [
                self::Eq, self::Neq,
                self::Lt, self::Lte, self::Gt, self::Gte,
                self::Between, self::NotBetween,
                self::IsNull, self::IsNotNull,
            ],
            EavCast::Boolean => [
                self::IsTrue, self::IsFalse,
                self::IsNull,
            ],
            EavCast::Datetime => [
                self::Eq, self::Neq,
                self::Lt, self::Lte, self::Gt, self::Gte,
                self::Between, self::NotBetween,
                self::IsNull, self::IsNotNull,
            ],
            EavCast::Json => [
                self::ContainsAny, self::ContainsAll, self::ContainsNone,
                self::IsNull, self::IsNotNull,
            ],
        };
    }

    /**
     * Whether this operator requires no user-provided value.
     */
    public function isUnary(): bool
    {
        return in_array($this, [
            self::IsNull, self::IsNotNull,
            self::IsEmpty, self::IsNotEmpty,
            self::IsTrue, self::IsFalse,
        ]);
    }

    /**
     * Whether this operator expects a two-element array value.
     */
    public function isRange(): bool
    {
        return in_array($this, [self::Between, self::NotBetween]);
    }

    /**
     * Whether this operator expects an array of values.
     */
    public function isMultiValue(): bool
    {
        return in_array($this, [
            self::In, self::NotIn,
            self::ContainsAny, self::ContainsAll, self::ContainsNone,
        ]);
    }
}
