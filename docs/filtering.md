# Filtering

Filament Studio provides an advanced filtering system with 23 operators, nested AND/OR logic, saved filters, and dynamic variable support.

## Filter Operators

Operators are context-sensitive — the available operators depend on the field's data type (`EavCast`).

### Universal Operators

Available for all data types:

| Operator | Key | Description |
|----------|-----|-------------|
| Equals | `eq` | Exact match |
| Not Equals | `neq` | Not an exact match |
| Is Null | `is_null` | Value is null (unary) |
| Is Not Null | `is_not_null` | Value is not null (unary) |

### Text Operators

Available for `Text` fields:

| Operator | Key | Description |
|----------|-----|-------------|
| Contains | `contains` | Substring match (LIKE %value%) |
| Not Contains | `not_contains` | No substring match |
| Starts With | `starts_with` | Prefix match (LIKE value%) |
| Ends With | `ends_with` | Suffix match (LIKE %value) |
| Is Empty | `is_empty` | Empty string (unary) |
| Is Not Empty | `is_not_empty` | Non-empty string (unary) |
| In | `in` | Value is in a list |
| Not In | `not_in` | Value is not in a list |

### Numeric & Datetime Operators

Available for `Integer`, `Decimal`, and `Datetime` fields. For datetime fields, labels adapt ("before" instead of "less than", etc.):

| Operator | Key | Description |
|----------|-----|-------------|
| Less Than | `lt` | Strictly less than |
| Less Than or Equal | `lte` | Less than or equal |
| Greater Than | `gt` | Strictly greater than |
| Greater Than or Equal | `gte` | Greater than or equal |
| Between | `between` | Within a range (expects two-element array) |
| Not Between | `not_between` | Outside a range |

### Boolean Operators

Available for `Boolean` fields:

| Operator | Key | Description |
|----------|-----|-------------|
| Is True | `is_true` | Value is true (unary) |
| Is False | `is_false` | Value is false (unary) |

### JSON Operators

Available for `Json` fields (multi-select, tags, repeaters):

| Operator | Key | Description |
|----------|-----|-------------|
| Contains Any | `contains_any` | Array contains any of the given values |
| Contains All | `contains_all` | Array contains all of the given values |
| Contains None | `contains_none` | Array contains none of the given values |

## Filter Trees

Filters are structured as trees of groups and rules, allowing arbitrarily nested logic:

```json
{
  "logic": "and",
  "rules": [
    {
      "field": "status",
      "operator": "eq",
      "value": "active"
    },
    {
      "logic": "or",
      "rules": [
        {
          "field": "priority",
          "operator": "gte",
          "value": 5
        },
        {
          "field": "due_date",
          "operator": "lt",
          "value": "$NOW"
        }
      ]
    }
  ]
}
```

This translates to: `status = 'active' AND (priority >= 5 OR due_date < NOW)`.

### Building Blocks

- **FilterGroup** — Container with `logic` (`and` or `or`) and an array of child `rules`. Children can be other groups or individual rules, enabling arbitrary nesting depth.
- **FilterRule** — A single condition with `field`, `operator`, and optional `value`. May also include `relatedField` for relational lookups.

## Dynamic Values

Filter values can reference runtime variables using the `$` prefix:

| Variable | Resolves To |
|----------|-------------|
| `$CURRENT_USER` | Authenticated user's ID |
| `$CURRENT_TENANT` | Current tenant's ID |
| `$NOW` | Current datetime |
| `$NOW(+7 days)` | Relative datetime using any Carbon modifier |
| `$NOW(first day of month)` | Complex Carbon modifier |

Dynamic values are resolved at query time by the `DynamicValueResolver` service.

## Saved Filters

Users can save filter configurations for reuse. Saved filters are stored per collection and support sharing:

| Property | Description |
|----------|-------------|
| `name` | Filter name |
| `collection_id` | Associated collection |
| `filter_tree` | The filter tree as JSON |
| `is_shared` | Whether other users can see this filter |
| `created_by` | Creator's user ID |
| `tenant_id` | Tenant scope |

Shared filters are visible to all users in the same tenant. Non-shared filters are private to the creator.

## Filter Builder UI

The `FilterBuilder` Livewire component provides an interactive UI for constructing filter trees. It supports:

- Adding rules and nested groups
- Toggling AND/OR logic per group
- Removing rules
- Context-aware operator selection based on field type
- Applying or clearing filters
- Saving and loading filter presets
