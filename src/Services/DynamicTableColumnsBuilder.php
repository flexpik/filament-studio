<?php

namespace Flexpik\FilamentStudio\Services;

use Filament\Tables\Columns\Column;
use Flexpik\FilamentStudio\FieldTypes\FieldTypeRegistry;
use Flexpik\FilamentStudio\FilamentStudioPlugin;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Illuminate\Database\Eloquent\Collection;

class DynamicTableColumnsBuilder
{
    /**
     * Build a Filament table columns array from a collection's visible fields.
     *
     * @return array<Column>
     */
    public static function build(StudioCollection $collection): array
    {
        $registry = app(FieldTypeRegistry::class);

        /** @var Collection<int, StudioField> $fieldsCollection */
        $fieldsCollection = $collection->fields()
            ->where('is_hidden_in_table', false)
            ->orderBy('is_system')
            ->orderBy('sort_order')
            ->get();

        $columns = $fieldsCollection
            ->map(fn (StudioField $field) => $registry->make($field)->buildTableColumn())
            ->filter()
            ->values()
            ->all();

        $columns = FilamentStudioPlugin::applyModifyTableColumns($columns, $collection);

        return $columns;
    }
}
