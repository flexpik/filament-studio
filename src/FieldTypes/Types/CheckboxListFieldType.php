<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;
use Flexpik\FilamentStudio\Models\StudioFieldOption;

class CheckboxListFieldType extends AbstractFieldType
{
    public static string $key = 'checkbox_list';

    public static string $label = 'Checkbox List';

    public static string $icon = 'heroicon-o-queue-list';

    public static EavCast $eavCast = EavCast::Json;

    public static string $category = 'selection';

    public static function settingsSchema(): array
    {
        return [
            Toggle::make('searchable')->label('Searchable')->default(false)
                ->helperText('Enable search filtering when there are many options.'),
            TextInput::make('columns')
                ->numeric()
                ->default(1)
                ->label('Number of Columns')
                ->helperText('Number of columns to arrange the checkboxes in.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $checkboxList = CheckboxList::make($this->field->column_name);

        $options = StudioFieldOption::where('field_id', $this->field->id)
            ->orderBy('sort_order')
            ->pluck('label', 'value')
            ->all();
        $checkboxList->options($options);

        if ($this->setting('searchable')) {
            $checkboxList->searchable();
        }

        if ($this->setting('columns')) {
            $checkboxList->columns((int) $this->setting('columns'));
        }

        return $checkboxList;
    }

    public function toTableColumn(): ?Column
    {
        $options = StudioFieldOption::where('field_id', $this->field->id)
            ->orderBy('sort_order')
            ->pluck('label', 'value')
            ->all();

        return TextColumn::make($this->field->column_name)
            ->badge()
            ->formatStateUsing(function (mixed $state) use ($options): string {
                $values = is_string($state) ? (json_decode($state, true) ?? [$state]) : (array) $state;

                return collect($values)->map(fn ($v) => $options[$v] ?? $v)->implode(', ');
            })
            ->separator(', ');
    }

    public function toFilter(): ?BaseFilter
    {
        $options = StudioFieldOption::where('field_id', $this->field->id)
            ->orderBy('sort_order')
            ->pluck('label', 'value')
            ->all();

        return SelectFilter::make($this->field->column_name)
            ->multiple()
            ->options($options)
            ->label($this->field->label ?? $this->field->column_name);
    }
}
