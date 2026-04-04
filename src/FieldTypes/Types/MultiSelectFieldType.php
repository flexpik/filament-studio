<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;
use Flexpik\FilamentStudio\Models\StudioFieldOption;

class MultiSelectFieldType extends AbstractFieldType
{
    public static string $key = 'multi_select';

    public static string $label = 'Multi Select';

    public static string $icon = 'heroicon-o-list-bullet';

    public static EavCast $eavCast = EavCast::Json;

    public static string $category = 'selection';

    public static function settingsSchema(): array
    {
        return [
            Toggle::make('searchable')->label('Searchable')->default(true)
                ->helperText('Enable type-ahead search to filter options.'),
            Toggle::make('preload')->label('Preload Options')->default(false)
                ->helperText('Load all options immediately instead of on-demand.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $select = Select::make($this->field->column_name)
            ->multiple();

        $options = StudioFieldOption::where('field_id', $this->field->id)
            ->orderBy('sort_order')
            ->pluck('label', 'value')
            ->all();
        $select->options($options);

        if ($this->setting('searchable', true)) {
            $select->searchable();
        }

        if ($this->setting('preload')) {
            $select->preload();
        }

        return $select;
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
