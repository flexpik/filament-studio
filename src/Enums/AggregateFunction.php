<?php

namespace Flexpik\FilamentStudio\Enums;

enum AggregateFunction: string
{
    case Count = 'count';
    case CountDistinct = 'count_distinct';
    case Avg = 'avg';
    case AvgDistinct = 'avg_distinct';
    case Sum = 'sum';
    case SumDistinct = 'sum_distinct';
    case Min = 'min';
    case Max = 'max';

    public function label(): string
    {
        return match ($this) {
            self::Count => 'Count',
            self::CountDistinct => 'Count (Distinct)',
            self::Avg => 'Average',
            self::AvgDistinct => 'Average (Distinct)',
            self::Sum => 'Sum',
            self::SumDistinct => 'Sum (Distinct)',
            self::Min => 'Minimum',
            self::Max => 'Maximum',
        };
    }

    /**
     * @return list<self>
     */
    public static function forCast(EavCast $cast): array
    {
        $orderable = [self::Count, self::CountDistinct, self::Min, self::Max];
        $numeric = [self::Avg, self::AvgDistinct, self::Sum, self::SumDistinct];

        return match ($cast) {
            EavCast::Integer, EavCast::Decimal => [...$orderable, ...$numeric],
            EavCast::Datetime => [...$orderable],
            EavCast::Text, EavCast::Boolean, EavCast::Json => [self::Count, self::CountDistinct],
        };
    }

    public function requiresField(): bool
    {
        return $this !== self::Count;
    }

    public function toSql(string $column): string
    {
        return match ($this) {
            self::Count => "COUNT({$column})",
            self::CountDistinct => "COUNT(DISTINCT {$column})",
            self::Avg => "AVG({$column})",
            self::AvgDistinct => "AVG(DISTINCT {$column})",
            self::Sum => "SUM({$column})",
            self::SumDistinct => "SUM(DISTINCT {$column})",
            self::Min => "MIN({$column})",
            self::Max => "MAX({$column})",
        };
    }
}
