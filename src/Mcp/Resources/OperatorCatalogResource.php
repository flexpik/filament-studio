<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Resources;

use Flexpik\FilamentStudio\Enums\FilterOperator;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Uri('studio://operators')]
#[MimeType('application/json')]
#[Description('All filter operators (with arity, label) plus the FilterGroup JSON shape used by studio_query_records.')]
class OperatorCatalogResource extends Resource
{
    public function handle(Request $request): Response
    {
        $operators = [];
        foreach (FilterOperator::cases() as $op) {
            $operators[] = [
                'key' => $op->value,
                'label' => $op->label(),
                'arity' => $this->arity($op),
            ];
        }

        $payload = [
            'operators' => $operators,
            'filter_group_shape' => [
                'description' => 'A FilterGroup is a logical AND/OR node containing FilterRule leaves and child FilterGroup nodes. Used by studio_query_records and saved filters.',
                'example' => [
                    'logic' => 'and',
                    'children' => [
                        ['field' => 'status', 'operator' => 'eq', 'value' => 'active'],
                        [
                            'logic' => 'or',
                            'children' => [
                                ['field' => 'price', 'operator' => 'gt', 'value' => 100],
                                ['field' => 'is_featured', 'operator' => 'is_true'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return Response::text(json_encode($payload, JSON_PRETTY_PRINT));
    }

    protected function arity(FilterOperator $op): int
    {
        if ($op->isUnary()) {
            return 0;
        }

        if ($op->isRange()) {
            return 2;
        }

        if ($op->isMultiValue()) {
            return -1; // n
        }

        return 1;
    }
}
