<?php

namespace Flexpik\FilamentStudio\Services;

use Closure;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;

class ConditionEvaluator
{
    /** @var array<string, array{callable: callable, reactive: bool}> */
    protected static array $resolvers = [];

    /** @var array<string, bool> */
    protected static array $resolverCache = [];

    // --- Instance: Per-field closure building ---

    /** @var array<string, array{logic: string, rules: array}> */
    protected array $parsedConditions = [];

    public function __construct(
        protected array $conditions,
        protected ?string $pageContext = null,
        protected ?User $user = null,
    ) {
        $this->parsedConditions = $this->parseConditions($conditions);
    }

    public function hasVisible(): bool
    {
        return isset($this->parsedConditions['visible']) && count($this->parsedConditions['visible']['rules']) > 0;
    }

    public function hasRequired(): bool
    {
        return isset($this->parsedConditions['required']) && count($this->parsedConditions['required']['rules']) > 0;
    }

    public function hasDisabled(): bool
    {
        return isset($this->parsedConditions['disabled']) && count($this->parsedConditions['disabled']['rules']) > 0;
    }

    public function buildVisibleClosure(): Closure
    {
        return $this->buildClosureForTarget('visible');
    }

    public function buildDehydratedClosure(): Closure
    {
        return $this->buildClosureForTarget('visible');
    }

    public function buildRequiredClosure(): Closure
    {
        return $this->buildClosureForTarget('required');
    }

    public function buildDisabledClosure(): Closure
    {
        return $this->buildClosureForTarget('disabled');
    }

    // --- Static: External Resolver Registry ---

    public static function registerResolver(string $key, callable $resolver, bool $reactive = false): void
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
            throw new \InvalidArgumentException("Resolver key must match [a-zA-Z0-9_]+, got: {$key}");
        }

        static::$resolvers[$key] = ['callable' => $resolver, 'reactive' => $reactive];
    }

    public static function resolve(string $key, array $recordState, ?User $user): bool
    {
        if (! isset(static::$resolvers[$key])) {
            static::logWarning("ConditionEvaluator: resolver '{$key}' is not registered, using permissive fallback.");

            return true;
        }

        $resolver = static::$resolvers[$key];

        // Non-reactive: cache per request
        if (! $resolver['reactive']) {
            $cacheKey = $key.'_'.($user?->getKey() ?? 'guest');

            if (array_key_exists($cacheKey, static::$resolverCache)) {
                return static::$resolverCache[$cacheKey];
            }

            $result = (bool) ($resolver['callable'])($recordState, $user);
            static::$resolverCache[$cacheKey] = $result;

            return $result;
        }

        return (bool) ($resolver['callable'])($recordState, $user);
    }

    /** @return string[] */
    public static function getRegisteredResolverKeys(): array
    {
        return array_keys(static::$resolvers);
    }

    public static function resetResolvers(): void
    {
        static::$resolvers = [];
        static::$resolverCache = [];
    }

    /**
     * Scan all fields and return column_names that are referenced by any field_value rule.
     *
     * @return string[]
     */
    public static function collectTriggerFields(Collection $fields): array
    {
        $triggerFields = [];

        foreach ($fields as $field) {
            $conditions = $field->settings['conditions'] ?? [];

            foreach (['visible', 'required', 'disabled'] as $target) {
                $rules = $conditions[$target]['rules'] ?? [];

                if (! is_array($rules)) {
                    continue;
                }

                foreach ($rules as $rule) {
                    if (($rule['type'] ?? '') === 'field_value' && isset($rule['field'])) {
                        $triggerFields[] = $rule['field'];
                    }
                }
            }
        }

        return array_values(array_unique($triggerFields));
    }

    /**
     * Detect circular dependencies in field_value conditions.
     *
     * @return string[]|null Cycle path if found, null if clean
     */
    public static function detectCycles(Collection $fields): ?array
    {
        $graph = [];
        foreach ($fields as $field) {
            $columnName = $field->column_name;
            $conditions = $field->settings['conditions'] ?? [];
            $deps = [];

            foreach (['visible', 'required', 'disabled'] as $target) {
                $rules = $conditions[$target]['rules'] ?? [];

                if (! is_array($rules)) {
                    continue;
                }

                foreach ($rules as $rule) {
                    if (($rule['type'] ?? '') === 'field_value' && isset($rule['field'])) {
                        $deps[] = $rule['field'];
                    }
                }
            }

            if (count($deps) > 0) {
                $graph[$columnName] = array_unique($deps);
            }
        }

        $visited = [];
        $inStack = [];
        $path = [];

        foreach (array_keys($graph) as $node) {
            if ($cycle = static::dfs($node, $graph, $visited, $inStack, $path)) {
                return $cycle;
            }
        }

        return null;
    }

    /**
     * @return string[]|null
     */
    protected static function dfs(string $node, array $graph, array &$visited, array &$inStack, array &$path): ?array
    {
        if (isset($inStack[$node])) {
            $cycleStart = array_search($node, $path);

            return array_merge(array_slice($path, $cycleStart), [$node]);
        }

        if (isset($visited[$node])) {
            return null;
        }

        $visited[$node] = true;
        $inStack[$node] = true;
        $path[] = $node;

        foreach ($graph[$node] ?? [] as $dep) {
            if ($cycle = static::dfs($dep, $graph, $visited, $inStack, $path)) {
                return $cycle;
            }
        }

        unset($inStack[$node]);
        array_pop($path);

        return null;
    }

    protected static function logWarning(string $message, array $context = []): void
    {
        if (Facade::getFacadeApplication()) {
            Log::warning($message, $context);
        }
    }

    /**
     * Parse and validate conditions, filtering out malformed rules.
     *
     * @return array<string, array{logic: string, rules: array}>
     */
    protected function parseConditions(array $conditions): array
    {
        $parsed = [];

        foreach (['visible', 'required', 'disabled'] as $target) {
            if (! isset($conditions[$target]) || ! is_array($conditions[$target])) {
                continue;
            }

            $targetConfig = $conditions[$target];
            $logic = $targetConfig['logic'] ?? 'and';
            $rules = $targetConfig['rules'] ?? [];

            if (! is_array($rules)) {
                continue;
            }

            $validRules = [];
            foreach ($rules as $rule) {
                if (! is_array($rule) || ! isset($rule['type'])) {
                    static::logWarning('ConditionEvaluator: skipping rule with missing type', ['rule' => $rule]);

                    continue;
                }

                if (! in_array($rule['type'], ['field_value', 'permission', 'record_state', 'external'])) {
                    static::logWarning("ConditionEvaluator: skipping unknown rule type '{$rule['type']}'");

                    continue;
                }

                $validRules[] = $rule;
            }

            if (count($validRules) > 0) {
                $parsed[$target] = ['logic' => $logic, 'rules' => $validRules];
            }
        }

        return $parsed;
    }

    protected function buildClosureForTarget(string $target): Closure
    {
        if (! isset($this->parsedConditions[$target])) {
            return fn (callable $get): bool => true;
        }

        $config = $this->parsedConditions[$target];
        $logic = $config['logic'];
        $rules = $config['rules'];

        $resolvedRules = [];
        foreach ($rules as $rule) {
            match ($rule['type']) {
                'permission' => $resolvedRules[] = [
                    'type' => 'static',
                    'value' => $this->evaluatePermissionRule($rule),
                ],
                'record_state' => $resolvedRules[] = [
                    'type' => 'static',
                    'value' => $this->evaluateRecordStateRule($rule),
                ],
                'external' => $resolvedRules[] = $this->prepareExternalRule($rule),
                'field_value' => $resolvedRules[] = $rule,
                default => null,
            };
        }

        return function (callable $get) use ($resolvedRules, $logic): bool {
            $results = [];

            foreach ($resolvedRules as $rule) {
                $results[] = match ($rule['type']) {
                    'static' => $rule['value'],
                    'field_value' => $this->evaluateFieldValueRule($rule, $get),
                    'external_reactive' => static::resolve($rule['resolver'], [], $this->user),
                    default => true,
                };
            }

            if (count($results) === 0) {
                return true;
            }

            return $logic === 'or'
                ? in_array(true, $results, true)
                : ! in_array(false, $results, true);
        };
    }

    protected function evaluateFieldValueRule(array $rule, callable $get): bool
    {
        $fieldValue = $get($rule['field'] ?? '');
        $op = $rule['op'] ?? 'equals';
        $compareValue = $rule['value'] ?? null;

        return match ($op) {
            'equals' => $fieldValue == $compareValue,
            'not_equals' => $fieldValue != $compareValue,
            'in' => is_array($compareValue) && in_array($fieldValue, $compareValue),
            'not_in' => is_array($compareValue) && ! in_array($fieldValue, $compareValue),
            'is_empty' => empty($fieldValue) && $fieldValue !== 0 && $fieldValue !== false,
            'is_not_empty' => ! empty($fieldValue) || $fieldValue === 0 || $fieldValue === false,
            'greater_than' => is_numeric($fieldValue) && is_numeric($compareValue) && $fieldValue > $compareValue,
            'less_than' => is_numeric($fieldValue) && is_numeric($compareValue) && $fieldValue < $compareValue,
            'contains' => $this->evaluateContains($fieldValue, $compareValue),
            default => true,
        };
    }

    protected function evaluateContains(mixed $fieldValue, mixed $compareValue): bool
    {
        if (is_array($fieldValue)) {
            return in_array($compareValue, $fieldValue);
        }

        if (is_string($fieldValue) && is_string($compareValue)) {
            return str_contains($fieldValue, $compareValue);
        }

        return false;
    }

    protected function evaluatePermissionRule(array $rule): bool
    {
        if (! $this->user) {
            return false;
        }

        $gate = $rule['gate'] ?? '';

        try {
            $result = $this->user->can($gate);
        } catch (\Throwable) {
            $result = false;
        }

        if ($rule['negate'] ?? false) {
            $result = ! $result;
        }

        return $result;
    }

    protected function evaluateRecordStateRule(array $rule): bool
    {
        if ($this->pageContext === null) {
            return true;
        }

        $state = $rule['state'] ?? '';

        return $this->pageContext === $state;
    }

    protected function prepareExternalRule(array $rule): array
    {
        $resolverKey = $rule['resolver'] ?? '';
        $isReactive = $rule['reactive'] ?? false;

        if ($isReactive) {
            return ['type' => 'external_reactive', 'resolver' => $resolverKey];
        }

        return [
            'type' => 'static',
            'value' => static::resolve($resolverKey, [], $this->user),
        ];
    }
}
