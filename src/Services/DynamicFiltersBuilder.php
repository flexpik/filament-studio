<?php

namespace Flexpik\FilamentStudio\Services;

use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\FieldTypes\FieldTypeRegistry;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Illuminate\Database\Eloquent\Collection;

class DynamicFiltersBuilder
{
    /**
     * Build a Filament table filters array from a collection's filterable fields.
     *
     * @return array<BaseFilter>
     */
    public static function build(StudioCollection $collection): array
    {
        $registry = app(FieldTypeRegistry::class);

        /** @var Collection<int, StudioField> $fields */
        $fields = $collection->fields()
            ->where('is_filterable', true)
            ->orderBy('sort_order')
            ->get();

        return $fields
            ->map(fn (StudioField $field) => $registry->make($field)->toFilter())
            ->filter()
            ->values()
            ->all();
    }
}
