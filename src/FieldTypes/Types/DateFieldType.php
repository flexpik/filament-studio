<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;
use Illuminate\Database\Eloquent\Builder;

class DateFieldType extends AbstractFieldType
{
    public static string $key = 'date';

    public static string $label = 'Date';

    public static string $icon = 'heroicon-o-calendar';

    public static EavCast $eavCast = EavCast::Datetime;

    public static string $category = 'datetime';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('display_format')->label('Display Format')->default('Y-m-d')->placeholder('Y-m-d')->helperText('PHP date format for display. E.g. d/m/Y, M j, Y.'),
            Toggle::make('close_on_select')->label('Close on Select')->default(true)->helperText('Automatically close the date picker after selecting a date.'),
            Toggle::make('native')->label('Native Picker')->default(false)->helperText('Use the browser\'s built-in date picker instead of the custom one.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $picker = DatePicker::make($this->field->column_name);

        if ($this->setting('display_format')) {
            $picker->displayFormat($this->setting('display_format'));
        }

        if ($this->setting('close_on_select', true)) {
            $picker->closeOnDateSelection();
        }

        if ($this->setting('native')) {
            $picker->native();
        }

        return $picker;
    }

    public function toTableColumn(): ?Column
    {
        $format = $this->setting('display_format', 'Y-m-d');

        return TextColumn::make($this->field->column_name)
            ->date($format);
    }

    public function toFilter(): ?BaseFilter
    {
        return Filter::make($this->field->column_name)
            ->form([
                DatePicker::make('from')->label('From'),
                DatePicker::make('until')->label('Until'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when($data['from'], fn (Builder $q, $date) => $q->where($this->field->column_name, '>=', $date))
                    ->when($data['until'], fn (Builder $q, $date) => $q->where($this->field->column_name, '<=', $date));
            });
    }
}
