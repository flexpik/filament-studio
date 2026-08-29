<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Logic;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;

class ConditionActivity implements FlowOperation
{
    public function execute(OperationContext $context): OperationResult
    {
        $config = $context->config();
        $filter = $config['filter'] ?? ['logic' => 'and', 'rules' => []];
        $bag = $context->dataChain()->toArray();

        $matched = $this->evaluate($filter, $bag);

        return OperationResult::withBranch(
            $matched ? 'success' : 'failure',
            ['result' => $matched],
        );
    }

    /**
     * @param  array<string, mixed>  $group
     * @param  array<string, mixed>  $bag
     */
    private function evaluate(array $group, array $bag): bool
    {
        $logic = strtolower($group['logic'] ?? 'and');
        $rules = $group['rules'] ?? [];

        if ($rules === []) {
            return true;
        }

        foreach ($rules as $rule) {
            $matches = isset($rule['rules'])
                ? $this->evaluate($rule, $bag)
                : $this->evaluateRule($rule, $bag);

            if ($logic === 'or' && $matches) {
                return true;
            }
            if ($logic === 'and' && ! $matches) {
                return false;
            }
        }

        return $logic === 'and';
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $bag
     */
    private function evaluateRule(array $rule, array $bag): bool
    {
        $value = $this->resolvePath((string) $rule['path'], $bag);
        $operator = $rule['operator'] ?? 'equals';
        $expected = $rule['value'] ?? null;

        return match ($operator) {
            'equals' => $value == $expected,
            'not_equals' => $value != $expected,
            'contains' => is_string($value) && is_string($expected) && str_contains($value, $expected),
            'gt' => $value > $expected,
            'gte' => $value >= $expected,
            'lt' => $value < $expected,
            'lte' => $value <= $expected,
            'is_null' => $value === null,
            'is_not_null' => $value !== null,
            'in' => is_array($expected) && in_array($value, $expected, true),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $bag
     */
    private function resolvePath(string $path, array $bag): mixed
    {
        $segments = explode('.', $path);
        $head = array_shift($segments);
        $node = $bag[$head] ?? null;
        foreach ($segments as $s) {
            if (! is_array($node) || ! array_key_exists($s, $node)) {
                return null;
            }
            $node = $node[$s];
        }

        return $node;
    }
}
