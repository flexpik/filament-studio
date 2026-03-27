<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class TimeFieldType extends AbstractFieldType
{
    public static string $key = 'time';

    public static string $label = 'Time';

    public static string $icon = 'heroicon-o-clock';

    public static EavCast $eavCast = EavCast::Datetime;

    public static string $category = 'datetime';

    public static function settingsSchema(): array
    {
        return [
            Toggle::make('seconds')->label('Show Seconds')->default(false)->helperText('Include seconds in the time picker.'),
            Toggle::make('native')->label('Native Picker')->default(false)->helperText('Use the browser\'s built-in time picker instead of the custom one.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $picker = TimePicker::make($this->field->column_name);

        if ($this->setting('seconds')) {
            $picker->seconds(true);
        } else {
            $picker->seconds(false);
        }

        if ($this->setting('native')) {
            $picker->native();
        }

        return $picker;
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name)
            ->time($this->setting('seconds') ? 'H:i:s' : 'H:i');
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}
