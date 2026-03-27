# Conditional Logic

Filament Studio supports dynamic field behavior based on conditions. Fields can be conditionally visible, required, or disabled based on form state, user permissions, page context, or custom logic.

## Condition Types

### Field Value

React to the current value of another field in the form:

```json
{
  "type": "field_value",
  "field": "status",
  "operator": "equals",
  "value": "published"
}
```

**Available operators:** `equals`, `not_equals`, `in`, `not_in`, `is_empty`, `is_not_empty`, `greater_than`, `less_than`, `contains`.

### Permission

Check if the current user has a specific Laravel Gate permission:

```json
{
  "type": "permission",
  "permission": "manage-pricing"
}
```

### Record State

React to the current page context (creating a new record vs editing an existing one):

```json
{
  "type": "record_state",
  "state": "create"
}
```

Valid states: `create`, `edit`.

### External Resolver

Use custom logic registered via the plugin API:

```json
{
  "type": "external",
  "resolver": "has_premium"
}
```

External resolvers are registered when configuring the plugin:

```php
FilamentStudioPlugin::make()
    ->conditionResolver('has_premium', function () {
        return auth()->user()->isPremium();
    }, reactive: true);
```

The `reactive` parameter controls whether the form re-evaluates this condition on every change. Set to `true` for conditions that depend on form state.

## Applying Conditions

Conditions can control three field behaviors:

- **Visible** — Whether the field is shown in the form
- **Required** — Whether the field must have a value
- **Disabled** — Whether the field is read-only

Each behavior accepts an array of condition rules. Multiple rules within a behavior use AND logic — all must be satisfied.

## Condition Evaluator

The `ConditionEvaluator` service handles all condition logic. It:

- Builds Filament-compatible closures for `->visible()`, `->required()`, `->disabled()`, and `->dehydrated()`
- Collects trigger fields to make them `->live()` for reactive updates
- Detects circular dependencies between field conditions
- Supports both built-in condition types and registered external resolvers

### Cycle Detection

The evaluator includes static analysis to detect circular dependencies. If field A's visibility depends on field B, and field B's visibility depends on field A, the evaluator will detect and report the cycle.

```php
$cycles = ConditionEvaluator::detectCycles($fields);
// Returns null if no cycles, or array describing the cycle path
```

## Registering External Resolvers

External resolvers receive the current record state and authenticated user:

```php
ConditionEvaluator::registerResolver(
    key: 'is_business_hours',
    resolver: function (array $recordState, ?User $user): bool {
        $hour = now()->hour;
        return $hour >= 9 && $hour < 17;
    },
    reactive: false
);
```

Or more commonly, through the plugin API:

```php
FilamentStudioPlugin::make()
    ->conditionResolver('is_business_hours', function () {
        return now()->hour >= 9 && now()->hour < 17;
    });
```
