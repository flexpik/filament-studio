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

class DecimalFieldType extends AbstractFieldType
{
    public static string $key = 'decimal';

    public static string $label = 'Decimal';

    public static string $icon = 'heroicon-o-variable';

    public static EavCast $eavCast = EavCast::Decimal;

    public static string $category = 'numeric';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('precision')->numeric()->default(20)->label('Precision (total digits)')->helperText('Total number of digits (including decimal places).'),
            TextInput::make('scale')->numeric()->default(6)->label('Scale (decimal places)')->helperText('Number of decimal places. E.g. scale=2 means values like 99.99.'),
            TextInput::make('min')->numeric()->label('Minimum Value')->placeholder('e.g. 0.00')->helperText('Minimum allowed value.'),
            TextInput::make('max')->numeric()->label('Maximum Value')->placeholder('e.g. 99999.99')->helperText('Maximum allowed value.'),
            TextInput::make('step')->numeric()->label('Step')->placeholder('e.g. 0.01')->helperText('Increment step for up/down arrows.'),
            TextInput::make('prefix')->label('Prefix')->placeholder('e.g. $')->helperText('Text or symbol displayed before the input.'),
            TextInput::make('suffix')->label('Suffix')->placeholder('e.g. %')->helperText('Text or symbol displayed after the input.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $input = TextInput::make($this->field->column_name)
            ->numeric();

        $scale = $this->setting('scale', 6);
        $input->step(1 / (10 ** $scale));

        if ($this->setting('min') !== null) {
            $input->minValue((float) $this->setting('min'));
        }

        if ($this->setting('max') !== null) {
            $input->maxValue((float) $this->setting('max'));
        }

        if ($this->setting('step') !== null) {
            $input->step((float) $this->setting('step'));
        }

        if ($this->setting('prefix')) {
            $input->prefix($this->setting('prefix'));
        }

        if ($this->setting('suffix')) {
            $input->suffix($this->setting('suffix'));
        }

        return $input;
    }

    public function toTableColumn(): ?Column
    {
        $scale = $this->setting('scale', 6);

        return TextColumn::make($this->field->column_name)
            ->numeric(decimalPlaces: (int) $scale);
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
