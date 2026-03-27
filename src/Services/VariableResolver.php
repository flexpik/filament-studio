<?php

namespace Flexpik\FilamentStudio\Services;

use Filament\Facades\Filament;
use Illuminate\Support\Carbon;

class VariableResolver
{
    /**
     * Resolve a single value that may contain a variable token.
     *
     * @param  array<string, mixed>  $variables
     */
    public function resolve(mixed $value, array $variables, ?string $recordUuid): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return match (true) {
            $value === '$CURRENT_RECORD' => $recordUuid,
            $value === '$CURRENT_USER' => auth()->id(),
            $value === '$CURRENT_TENANT' => Filament::getTenant()?->getKey(),
            $value === '$NOW' => now()->toDateTimeString(),
            str_starts_with($value, '$NOW(') => $this->resolveNowAdjustment($value),
            str_starts_with($value, '{{') && str_ends_with($value, '}}') => $variables[trim($value, '{} ')] ?? null,
            default => $value,
        };
    }

    /**
     * Recursively resolve all variable tokens in a filter tree array.
     *
     * @param  array<string, mixed>  $tree
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    public function resolveTree(array $tree, array $variables, ?string $recordUuid): array
    {
        return $this->walkAndResolve($tree, $variables, $recordUuid);
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    protected function walkAndResolve(array $node, array $variables, ?string $recordUuid): array
    {
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = $this->walkAndResolve($value, $variables, $recordUuid);
            } else {
                $node[$key] = $this->resolve($value, $variables, $recordUuid);
            }
        }

        return $node;
    }

    protected function resolveNowAdjustment(string $value): string
    {
        $inner = substr($value, 5, -1);

        return Carbon::now()->modify($inner)->toDateTimeString();
    }
}
