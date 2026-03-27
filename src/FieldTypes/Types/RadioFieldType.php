<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;
use Flexpik\FilamentStudio\Models\StudioFieldOption;

class RadioFieldType extends AbstractFieldType
{
    public static string $key = 'radio';

    public static string $label = 'Radio';

    public static string $icon = 'heroicon-o-stop-circle';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'selection';

    public static function settingsSchema(): array
    {
        return [
            Toggle::make('inline')
                ->label('Display Inline')
                ->default(false)
                ->helperText('Display radio buttons in a horizontal row instead of stacked vertically.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $radio = Radio::make($this->field->column_name);

        $options = StudioFieldOption::where('field_id', $this->field->id)
            ->orderBy('sort_order')
            ->pluck('label', 'value')
            ->all();
        $radio->options($options);

        if ($this->setting('inline')) {
            $radio->inline();
        }

        return $radio;
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name)
            ->badge();
    }

    public function toFilter(): ?BaseFilter
    {
        $options = StudioFieldOption::where('field_id', $this->field->id)
            ->orderBy('sort_order')
            ->pluck('label', 'value')
            ->all();

        return SelectFilter::make($this->field->column_name)
            ->options($options)
            ->label($this->field->label ?? $this->field->column_name);
    }
}
