<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Collections;

use Flexpik\FilamentStudio\FieldTypes\FieldTypeRegistry;
use Flexpik\FilamentStudio\Mcp\Exceptions\IntegrityException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateCollection
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function __invoke(array $input, ?int $tenantId): StudioCollection
    {
        $maxFields = (int) config('filament-studio.mcp.limits.create_collection_max_fields', 50);

        $data = Validator::validate($input, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'regex:/^[a-z][a-z0-9-]*$/', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_singleton' => ['nullable', 'boolean'],
            'is_hidden' => ['nullable', 'boolean'],
            'api_enabled' => ['nullable', 'boolean'],
            'enable_versioning' => ['nullable', 'boolean'],
            'enable_soft_deletes' => ['nullable', 'boolean'],
            'archive_field' => ['nullable', 'string'],
            'supported_locales' => ['nullable', 'array'],
            'fields' => ['nullable', 'array', 'max:'.$maxFields],
            'fields.*.column_name' => ['required_with:fields', 'string', 'regex:/^[a-z][a-z0-9_]*$/'],
            'fields.*.field_type' => ['required_with:fields', 'string'],
            'fields.*.label' => ['nullable', 'string'],
            'fields.*.settings' => ['nullable', 'array'],
        ]);

        $slug = $data['slug'] ?? Str::slug($data['name']);

        return DB::transaction(function () use ($data, $slug, $tenantId) {
            try {
                $label = $data['label'] ?? $data['name'];

                $collection = StudioCollection::create([
                    'tenant_id' => $tenantId,
                    'name' => $data['name'],
                    'slug' => $slug,
                    'label' => $label,
                    'label_plural' => Str::plural($label),
                    'icon' => $data['icon'] ?? null,
                    'description' => $data['description'] ?? null,
                    'is_singleton' => $data['is_singleton'] ?? false,
                    'is_hidden' => $data['is_hidden'] ?? false,
                    'api_enabled' => $data['api_enabled'] ?? false,
                    'enable_versioning' => $data['enable_versioning'] ?? false,
                    'enable_soft_deletes' => $data['enable_soft_deletes'] ?? false,
                    'archive_field' => $data['archive_field'] ?? null,
                    'supported_locales' => $data['supported_locales'] ?? null,
                ]);
            } catch (QueryException $e) {
                if (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Duplicate')) {
                    throw IntegrityException::duplicate('slug', $slug);
                }
                throw $e;
            }

            $registry = app(FieldTypeRegistry::class);
            $types = $registry->all();

            foreach ($data['fields'] ?? [] as $i => $fieldData) {
                $typeClass = $types[$fieldData['field_type']] ?? null;
                $eavCast = $typeClass ? $typeClass::$eavCast->value : 'text';

                StudioField::create([
                    'collection_id' => $collection->id,
                    'tenant_id' => $tenantId,
                    'column_name' => $fieldData['column_name'],
                    'label' => $fieldData['label'] ?? Str::headline($fieldData['column_name']),
                    'field_type' => $fieldData['field_type'],
                    'eav_cast' => $eavCast,
                    'sort_order' => $i,
                    'settings' => $fieldData['settings'] ?? [],
                ]);
            }

            return $collection->load('fields');
        });
    }
}
