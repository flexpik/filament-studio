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
use Flexpik\FilamentStudio\Models\StudioCollection;

class BelongsToManyFieldType extends AbstractFieldType
{
    public static string $key = 'belongs_to_many';

    public static string $label = 'Belongs To Many';

    public static string $icon = 'heroicon-o-arrows-right-left';

    public static EavCast $eavCast = EavCast::Json;

    public static string $category = 'relational';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('related_collection')->label('Related Collection Slug')->required()->placeholder('e.g. tags')->helperText('The collection this many-to-many relationship references.'),
            TextInput::make('display_field')->label('Display Field')->default('name')->placeholder('name')->helperText('Which field from the related collection to display.'),
            Toggle::make('searchable')->label('Searchable')->default(true)->helperText('Enable type-ahead search in the multi-select dropdown.'),
            Toggle::make('preload')->label('Preload Options')->default(true)->helperText('Load all related records immediately. Recommended for small datasets.'),
            Toggle::make('tenant_scoped')->label('Tenant Scoped')->default(true)->helperText('Only show related records belonging to the current tenant.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $select = Select::make($this->field->column_name)->multiple();

        $relatedSlug = $this->setting('related_collection');

        if (empty($relatedSlug)) {
            $select->options([])
                ->disabled()
                ->placeholder('No related collection configured');

            return $select;
        }

        $displayField = $this->setting('display_field', 'name');

        $select->options(function () use ($relatedSlug, $displayField): array {
            $collection = StudioCollection::where('slug', $relatedSlug)->first();

            if (! $collection) {
                return [];
            }

            $field = $collection->fields()->where('column_name', $displayField)->first();

            if (! $field) {
                return [];
            }

            $valCol = $field->eav_cast->column();

            return $collection->records()
                ->join('studio_values', function ($join) use ($field) {
                    $join->on('studio_records.id', '=', 'studio_values.record_id')
                        ->where('studio_values.field_id', '=', $field->id);
                })
                ->pluck("studio_values.{$valCol}", 'studio_records.id')
                ->toArray();
        });

        if ($this->setting('searchable', true)) {
            $select->searchable();
        }

        if ($this->setting('preload', true)) {
            $select->preload();
        }

        return $select;
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name)->badge()->separator(',');
    }

    public function toFilter(): ?BaseFilter
    {
        return SelectFilter::make($this->field->column_name)
            ->multiple()
            ->label($this->field->label ?? $this->field->column_name)
            ->searchable();
    }
}
