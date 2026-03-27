<?php

namespace Flexpik\FilamentStudio\Services;

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;

class CollectionSeeder
{
    /**
     * Seed default collections and fields for a tenant.
     *
     * @param  array<int, array{
     *     name: string,
     *     label: string,
     *     label_plural: string,
     *     slug: string,
     *     icon?: string,
     *     description?: string,
     *     fields?: array<int, array{
     *         column_name: string,
     *         label: string,
     *         field_type: string,
     *         eav_cast: string,
     *         is_required?: bool,
     *         settings?: array,
     *     }>
     * }>  $collections
     */
    public static function seedForTenant(int|string $tenantId, array $collections): void
    {
        foreach ($collections as $collectionConfig) {
            $fields = $collectionConfig['fields'] ?? [];
            unset($collectionConfig['fields']);

            $collection = StudioCollection::create([
                ...$collectionConfig,
                'tenant_id' => $tenantId,
            ]);

            foreach ($fields as $sortOrder => $fieldConfig) {
                StudioField::create([
                    ...$fieldConfig,
                    'collection_id' => $collection->id,
                    'tenant_id' => $tenantId,
                    'sort_order' => $sortOrder,
                    'is_required' => $fieldConfig['is_required'] ?? false,
                    'settings' => $fieldConfig['settings'] ?? null,
                ]);
            }
        }
    }
}
