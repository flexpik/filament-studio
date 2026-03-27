<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class KeyValueFieldType extends AbstractFieldType
{
    public static string $key = 'key_value';

    public static string $label = 'Key-Value';

    public static string $icon = 'heroicon-o-table-cells';

    public static EavCast $eavCast = EavCast::Json;

    public static string $category = 'structured';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('key_label')->label('Key Column Label')->default('Key')->placeholder('Key')->helperText('Column header for the key column.'),
            TextInput::make('value_label')->label('Value Column Label')->default('Value')->placeholder('Value')->helperText('Column header for the value column.'),
            Toggle::make('reorderable')->label('Reorderable')->default(true)->helperText('Allow drag-and-drop reordering of key-value pairs.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $keyValue = KeyValue::make($this->field->column_name);

        if ($this->setting('key_label')) {
            $keyValue->keyLabel($this->setting('key_label'));
        }

        if ($this->setting('value_label')) {
            $keyValue->valueLabel($this->setting('value_label'));
        }

        if ($this->setting('reorderable', true)) {
            $keyValue->reorderable();
        }

        return $keyValue;
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name)
            ->formatStateUsing(fn ($state) => is_array($state) ? count($state).' entries' : '0 entries')
            ->badge();
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}
