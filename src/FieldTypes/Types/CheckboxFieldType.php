<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\TernaryFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class CheckboxFieldType extends AbstractFieldType
{
    public static string $key = 'checkbox';

    public static string $label = 'Checkbox';

    public static string $icon = 'heroicon-o-check';

    public static EavCast $eavCast = EavCast::Boolean;

    public static string $category = 'boolean';

    public static function settingsSchema(): array
    {
        return [];
    }

    public function toFilamentComponent(): Component
    {
        return Checkbox::make($this->field->column_name);
    }

    public function toTableColumn(): ?Column
    {
        return IconColumn::make($this->field->column_name)
            ->boolean();
    }

    public function toFilter(): ?BaseFilter
    {
        return TernaryFilter::make($this->field->column_name)
            ->label($this->field->label ?? $this->field->column_name);
    }
}
