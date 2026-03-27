# Dashboards & Panels

Filament Studio includes a dashboard builder with 9 built-in panel types for data visualization. Panels are placed in a 12-column grid layout and can display aggregated data, charts, lists, and interactive controls.

## Creating a Dashboard

Dashboards are managed through the Filament admin panel under **Studio > Dashboards**. Each dashboard has:

| Property | Description |
|----------|-------------|
| `name` | Display name |
| `slug` | URL-friendly identifier |
| `icon` | Heroicon name for navigation |
| `color` | Navigation accent color |
| `auto_refresh_interval` | Auto-refresh interval in seconds (optional) |
| `sort_order` | Order in navigation sidebar |

## Panel Placement

Panels can be placed in 5 contexts via the `PanelPlacement` enum:

| Placement | Description | Layout |
|-----------|-------------|--------|
| **Dashboard** | Main dashboard grid | 12-column grid |
| **Collection Header** | Above a collection's record list | Stacked |
| **Collection Footer** | Below a collection's record list | Stacked |
| **Record Header** | Above a record's detail view | Stacked |
| **Record Footer** | Below a record's detail view | Stacked |

Dashboard panels use grid positioning (`grid_col_span`, `grid_row_span`, `grid_order`). Non-dashboard panels use simple `sort_order` positioning.

## Panel Types

### Metric

Displays a single aggregated statistic (count, sum, average, min, max) with optional prefix, suffix, and conditional color styling.

**Configuration:**

| Option | Description |
|--------|-------------|
| `collection_id` | Source collection |
| `field` | Field to aggregate (not required for count) |
| `aggregate_function` | Count, Sum, Avg, Min, Max, or distinct variants |
| `prefix` / `suffix` | Text before/after the value (e.g., "$", "users") |
| `decimal_precision` | Number of decimal places |
| `abbreviate` | Shorten large numbers (e.g., 2000 → 2K) |
| `conditional_styles` | Array of `{operator, threshold, color}` rules |

### List

Displays a filtered and sorted list of records from a collection.

**Configuration:**

| Option | Description |
|--------|-------------|
| `collection_id` | Source collection |
| `display_template` | Template with `{{field_name}}` tokens |
| `sort_field` / `sort_direction` | Ordering |
| `limit` | Maximum records to display |
| `enable_inline_edit` | Allow editing records directly in the list |

### Time Series

Line or area chart showing data aggregated over time periods.

**Configuration:**

| Option | Description |
|--------|-------------|
| `collection_id` | Source collection |
| `date_field` | Datetime field for the X-axis |
| `value_field` | Numeric field to aggregate |
| `aggregate_function` | Aggregation method |
| `group_precision` | Grouping: Hour, Day, Week, Month, or Year |
| `date_range` | Time window: 7d, 30d, 90d, 1y, or all |
| `curve_type` | Smooth, Straight, or Stepline |
| `fill_type` | Gradient, Solid, or None |
| `show_axes` | Show axis labels |

### Bar Chart

Grouped bar chart with configurable aggregation.

**Configuration:**

| Option | Description |
|--------|-------------|
| `collection_id` | Source collection |
| `group_field` | Field to group bars by |
| `value_field` | Numeric field to aggregate |
| `aggregate_function` | Aggregation method |
| `horizontal` | Horizontal orientation |

### Line Chart

Multi-series line chart with individually configured series.

**Configuration:**

| Option | Description |
|--------|-------------|
| `collection_id` | Source collection |
| `group_field` | Field for the X-axis |
| `series` | Array of `{field, aggregate_function, label, color}` |

### Pie Chart

Pie or donut chart showing proportional data.

**Configuration:**

| Option | Description |
|--------|-------------|
| `collection_id` | Source collection |
| `group_field` | Field to segment by |
| `value_field` | Numeric field to aggregate |
| `aggregate_function` | Aggregation method |
| `donut` | Render as donut instead of pie |
| `show_labels` / `show_legend` | Display options |

### Meter

Gauge or progress indicator for a single value against a maximum.

**Configuration:**

| Option | Description |
|--------|-------------|
| `collection_id` | Source collection |
| `field` | Numeric field to display |
| `aggregate_function` | Aggregation method |
| `maximum` | Scale maximum |
| `size` | Full or half gauge |
| `stroke_width` | Arc thickness |
| `color` | Gauge color |
| `rounded_stroke` | Rounded arc ends |

**Supported placements:** Dashboard, Collection Header, Record Header only.

### Label

Static text heading for organizing or annotating dashboards.

**Configuration:**

| Option | Description |
|--------|-------------|
| `text` | Display text |
| `text_color` | Text color |
| `text_size` | Size: sm, base, lg, xl, 2xl |

### Variable

Interactive input that stores a value other panels can reference via `{{variable_key}}` tokens. This enables user-driven filtering and parameterization of dashboards.

**Configuration:**

| Option | Description |
|--------|-------------|
| `variable_key` | Token name (used as `{{key}}` in other panels) |
| `interface` | Input type: text, number, date, date_range, select |
| `default_value` | Initial value |
| `label` | Input label |

**Supported placements:** Dashboard only.

## Dynamic Variables

Panels support variable tokens that resolve at runtime:

| Variable | Resolves To |
|----------|-------------|
| `$CURRENT_USER` | Authenticated user ID |
| `$CURRENT_TENANT` | Current tenant ID |
| `$CURRENT_RECORD` | Current record UUID (in record context) |
| `$NOW` | Current datetime |
| `$NOW(+1 day)` | Relative datetime (any Carbon modifier) |
| `{{ variable_key }}` | Value from a Variable panel |

## Aggregate Functions

The following aggregate functions are available for panels:

| Function | Description | Requires Field |
|----------|-------------|----------------|
| Count | Count all records | No |
| Count Distinct | Count unique values | Yes |
| Sum | Sum of values | Yes |
| Sum Distinct | Sum of unique values | Yes |
| Avg | Average of values | Yes |
| Avg Distinct | Average of unique values | Yes |
| Min | Minimum value | Yes |
| Max | Maximum value | Yes |

Not all functions are available for all data types. Boolean fields only support Count. Text fields support Count and Count Distinct.
