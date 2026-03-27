<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\Select;
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

class SelectFieldType extends AbstractFieldType
{
    public static string $key = 'select';

    public static string $label = 'Select';

    public static string $icon = 'heroicon-o-chevron-up-down';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'selection';

    public static function settingsSchema(): array
    {
        return [
            Select::make('options_source')
                ->options(['static' => 'Static Options', 'collection' => 'From Collection'])
                ->default('static')
                ->live()
                ->helperText('Choose where options come from: static list defined here, or dynamically from another collection.'),
            TextInput::make('source_collection')
                ->label('Source Collection Slug')
                ->visible(fn (callable $get): bool => $get('options_source') === 'collection')
                ->helperText('The collection to pull option values from.'),
            TextInput::make('source_label_field')
                ->label('Display Field')
                ->visible(fn (callable $get): bool => $get('options_source') === 'collection')
                ->helperText('Which field from the source collection to display as the option label.'),
            Toggle::make('searchable')->label('Searchable')->default(false)
                ->helperText('Enable type-ahead search to filter options.'),
            Toggle::make('preload')->label('Preload Options')->default(false)
                ->helperText('Load all options immediately instead of on-demand. Recommended for small lists.'),
            Toggle::make('native')->label('Native Select')->default(false)
                ->helperText('Use the browser\'s built-in select instead of the enhanced dropdown.'),
            Toggle::make('allow_create')->label('Allow Creating New Options')->default(false)
                ->helperText('Allow users to create new options on-the-fly.'),
            Toggle::make('tenant_scoped')->label('Tenant Scoped')->default(true)
                ->helperText('Only show options belonging to the current tenant.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $select = Select::make($this->field->column_name);

        $optionsSource = $this->setting('options_source', 'static');

        if ($optionsSource === 'static') {
            $options = StudioFieldOption::where('field_id', $this->field->id)
                ->orderBy('sort_order')
                ->pluck('label', 'value')
                ->all();
            $select->options($options);
        }

        if ($this->setting('searchable')) {
            $select->searchable();
        }

        if ($this->setting('preload')) {
            $select->preload();
        }

        if ($this->setting('native')) {
            $select->native();
        }

        if ($this->setting('allow_create')) {
            $select->createOptionForm([
                TextInput::make('value')->required(),
                TextInput::make('label')->required(),
            ]);
        }

        return $select;
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
