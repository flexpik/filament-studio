<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class ColorFieldType extends AbstractFieldType
{
    public static string $key = 'color';

    public static string $label = 'Color Picker';

    public static string $icon = 'heroicon-o-swatch';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'structured';

    public static function settingsSchema(): array
    {
        return [
            Select::make('format')
                ->options(['hex' => 'Hex', 'rgb' => 'RGB', 'hsl' => 'HSL'])
                ->default('hex')
                ->helperText('Color output format. hex=#ff0000, rgb=rgb(255,0,0), hsl=hsl(0,100%,50%).'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $picker = ColorPicker::make($this->field->column_name);

        $format = $this->setting('format', 'hex');

        $picker = match ($format) {
            'rgb' => $picker->rgb(),
            'hsl' => $picker->hsl(),
            default => $picker->hex(),
        };

        return $picker;
    }

    public function toTableColumn(): ?Column
    {
        return ColorColumn::make($this->field->column_name);
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}
