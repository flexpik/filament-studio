<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Fields;

use Flexpik\FilamentStudio\Mcp\Exceptions\EavCastConflictException;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioValue;
use Illuminate\Support\Facades\Validator;

class UpdateField
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function __invoke(string $collectionSlug, string $columnName, array $input, int $tenantId): StudioField
    {
        $data = Validator::validate($input, [
            'column_name' => ['prohibited'],
            'label' => ['nullable', 'string', 'max:255'],
            'field_type' => ['nullable', 'string'],
            'eav_cast' => ['nullable', 'string', 'in:text,integer,decimal,boolean,datetime,json'],
            'settings' => ['nullable', 'array'],
            'is_required' => ['nullable', 'boolean'],
            'is_unique' => ['nullable', 'boolean'],
            'is_filterable' => ['nullable', 'boolean'],
            'is_translatable' => ['nullable', 'boolean'],
        ]);

        $collection = StudioCollection::query()
            ->forTenant($tenantId)
            ->where('slug', $collectionSlug)
            ->first();

        if ($collection === null) {
            throw new StudioNotFoundException('collection', $collectionSlug);
        }

        $field = StudioField::query()
            ->where('collection_id', $collection->id)
            ->where('column_name', $columnName)
            ->first();

        if ($field === null) {
            throw new StudioNotFoundException('field', "{$collectionSlug}.{$columnName}");
        }

        if (isset($data['eav_cast']) && $data['eav_cast'] !== $field->eav_cast->value) {
            $valueCount = StudioValue::query()->where('field_id', $field->id)->count();
            if ($valueCount > 0) {
                throw new EavCastConflictException(
                    field: $columnName,
                    from: $field->eav_cast->value,
                    to: $data['eav_cast'],
                    valueCount: $valueCount,
                );
            }
        }

        $field->fill(array_filter($data, fn ($v) => $v !== null))->save();

        return $field->fresh('options');
    }
}
