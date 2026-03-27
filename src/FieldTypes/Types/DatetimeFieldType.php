<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
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

class DatetimeFieldType extends AbstractFieldType
{
    public static string $key = 'datetime';

    public static string $label = 'Date & Time';

    public static string $icon = 'heroicon-o-calendar-days';

    public static EavCast $eavCast = EavCast::Datetime;

    public static string $category = 'datetime';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('display_format')->label('Display Format')->default('Y-m-d H:i')->placeholder('Y-m-d H:i')->helperText('PHP datetime format for display. E.g. d/m/Y H:i:s.'),
            TextInput::make('timezone')->label('Timezone')->default('UTC')->placeholder('UTC')->helperText('Timezone for display. Stored as UTC internally.'),
            Toggle::make('seconds')->label('Show Seconds')->default(false)->helperText('Show seconds selector in the time picker.'),
            Toggle::make('close_on_select')->label('Close on Date Selection')->default(true)->helperText('Automatically close the picker after selecting date and time.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $picker = DateTimePicker::make($this->field->column_name);

        if ($this->setting('display_format')) {
            $picker->displayFormat($this->setting('display_format'));
        }

        if ($this->setting('timezone')) {
            $picker->timezone($this->setting('timezone'));
        }

        if ($this->setting('seconds')) {
            $picker->seconds(true);
        } else {
            $picker->seconds(false);
        }

        if ($this->setting('close_on_select', true)) {
            $picker->closeOnDateSelection();
        }

        return $picker;
    }

    public function toTableColumn(): ?Column
    {
        $format = $this->setting('display_format', 'Y-m-d H:i');

        return TextColumn::make($this->field->column_name)
            ->dateTime($format);
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
