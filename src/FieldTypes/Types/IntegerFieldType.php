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

class IntegerFieldType extends AbstractFieldType
{
    public static string $key = 'integer';

    public static string $label = 'Integer';

    public static string $icon = 'heroicon-o-calculator';

    public static EavCast $eavCast = EavCast::Integer;

    public static string $category = 'numeric';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('min')->numeric()->label('Minimum Value')->placeholder('e.g. 0')->helperText('Minimum allowed value.'),
            TextInput::make('max')->numeric()->label('Maximum Value')->placeholder('e.g. 1000')->helperText('Maximum allowed value.'),
            TextInput::make('step')->numeric()->default(1)->label('Step')->helperText('Increment step when using up/down arrows.'),
            TextInput::make('prefix')->label('Prefix')->placeholder('e.g. #')->helperText('Text or symbol displayed before the input.'),
            TextInput::make('suffix')->label('Suffix')->placeholder('e.g. items')->helperText('Text or symbol displayed after the input.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $input = TextInput::make($this->field->column_name)
            ->numeric()
            ->integer();

        if ($this->setting('min') !== null) {
            $input->minValue((int) $this->setting('min'));
        }

        if ($this->setting('max') !== null) {
            $input->maxValue((int) $this->setting('max'));
        }

        if ($this->setting('step') !== null) {
            $input->step((int) $this->setting('step'));
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
