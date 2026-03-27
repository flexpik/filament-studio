<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;
use Illuminate\Database\Eloquent\Builder;

class RangeFieldType extends AbstractFieldType
{
    public static string $key = 'range';

    public static string $label = 'Range Slider';

    public static string $icon = 'heroicon-o-adjustments-horizontal';

    public static EavCast $eavCast = EavCast::Decimal;

    public static string $category = 'numeric';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('min')->numeric()->default(0)->label('Minimum Value')->helperText('Minimum slider value.'),
            TextInput::make('max')->numeric()->default(100)->label('Maximum Value')->helperText('Maximum slider value.'),
            TextInput::make('step')->numeric()->default(1)->label('Step')->helperText('Increment step between slider positions.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $input = TextInput::make($this->field->column_name)
            ->numeric()
            ->type('range');

        $min = $this->setting('min', 0);
        $max = $this->setting('max', 100);
        $step = $this->setting('step', 1);

        $input->minValue((float) $min);
        $input->maxValue((float) $max);
        $input->step((float) $step);

        return $input;
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name)
            ->numeric();
    }

    public function toFilter(): ?BaseFilter
    {
        return Filter::make($this->field->column_name)
            ->form([
                TextInput::make('min')->numeric()->label('Min'),
                TextInput::make('max')->numeric()->label('Max'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when($data['min'], fn (Builder $q, $min) => $q->where($this->field->column_name, '>=', $min))
                    ->when($data['max'], fn (Builder $q, $max) => $q->where($this->field->column_name, '<=', $max));
            });
    }
}
