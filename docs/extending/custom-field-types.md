# Custom Field Types

Filament Studio's field type system is fully extensible. You can create custom field types that integrate seamlessly with the form builder, table columns, filters, and EAV storage.

## Creating a Field Type

Extend `AbstractFieldType` and implement the required methods:

```php
<?php

namespace App\Studio\FieldTypes;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class CurrencyFieldType extends AbstractFieldType
{
    protected static string $key = 'currency';
    protected static string $label = 'Currency';
    protected static string $icon = 'heroicon-o-currency-dollar';
    protected static EavCast $eavCast = EavCast::Decimal;
    protected static string $category = 'numeric';

    public function settingsSchema(): array
    {
        return [
            TextInput::make('currency_code')
                ->label('Currency Code')
                ->default('USD')
                ->maxLength(3),
            TextInput::make('decimal_places')
                ->label('Decimal Places')
                ->numeric()
                ->default(2),
        ];
    }

    public function toFilamentComponent(): Component
    {
        return TextInput::make($this->field->column_name)
            ->numeric()
            ->prefix($this->setting('currency_code', 'USD'))
            ->step(0.01);
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name)
            ->money($this->setting('currency_code', 'USD'));
    }

    public function toFilter(): ?Filter
    {
        return null; // Or implement a custom filter
    }
}
```

## Required Properties

| Property | Type | Description |
|----------|------|-------------|
| `$key` | `string` | Unique identifier. Must match the key used in registration and stored in `studio_fields.field_type`. |
| `$label` | `string` | Human-readable display name. |
| `$icon` | `string` | Heroicon name for the field type picker. |
| `$eavCast` | `EavCast` | Determines which storage column is used. One of: `Text`, `Integer`, `Decimal`, `Boolean`, `Datetime`, `Json`. |
| `$category` | `string` | Grouping category for the field type picker. |

## Required Methods

### `settingsSchema(): array`

Returns a Filament form schema for type-specific configuration. These settings are stored in the field's `settings` JSON column and accessed via `$this->setting('key', $default)`.

### `toFilamentComponent(): Component`

Returns the Filament form component used for creating and editing records. Use `$this->field->column_name` as the component name.

The base class automatically applies common properties (label, placeholder, hint, required, disabled, width, validation) via `buildFormComponent()`. Your implementation should focus on type-specific configuration.

### `toTableColumn(): ?Column`

Returns the Filament table column for the record list view. Return `null` to exclude this field type from tables entirely.

### `toFilter(): ?Filter`

Returns a Filament table filter for this field type. Return `null` if the field type should not be filterable.

## Accessing Field Configuration

Within your field type class, you have access to:

```php
// The StudioField model instance
$this->field;
$this->field->column_name;  // Internal field name
$this->field->label;         // Display label
$this->field->is_required;   // Required flag
$this->field->settings;      // Full settings array

// Convenience accessor for settings
$this->setting('currency_code', 'USD');
$this->setting('decimal_places', 2);
```

## Registering Custom Field Types

Register your field types when configuring the plugin:

```php
use App\Studio\FieldTypes\CurrencyFieldType;
use App\Studio\FieldTypes\RatingFieldType;

FilamentStudioPlugin::make()
    ->fieldTypes([
        'currency' => CurrencyFieldType::class,
        'rating'   => RatingFieldType::class,
    ]);
```

The key in the array must match the `$key` static property on your field type class.

## EAV Cast Reference

Choose the appropriate `EavCast` for your field type's data:

| Cast | Storage Column | PHP Type | Use For |
|------|---------------|----------|---------|
| `Text` | `val_text` | `string` | Text, slugs, colors, file paths |
| `Integer` | `val_integer` | `int` | Whole numbers, counts, ratings |
| `Decimal` | `val_decimal` | `float` | Currency, percentages, measurements |
| `Boolean` | `val_boolean` | `bool` | Toggles, checkboxes, flags |
| `Datetime` | `val_datetime` | `Carbon` | Dates, times, timestamps |
| `Json` | `val_json` | `array` | Multi-select, repeaters, structured data |

## Data Transformers

If your field type needs custom serialization/deserialization (e.g., converting between stored and display formats), register a transformer:

```php
$registry = app(FieldTypeRegistry::class);

$registry->registerTransformer(
    fieldTypeKey: 'currency',
    serialize: fn ($value) => (float) $value,
    deserialize: fn ($value) => number_format((float) $value, 2),
);
```
