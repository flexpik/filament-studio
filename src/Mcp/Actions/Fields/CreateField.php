<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Fields;

use Flexpik\FilamentStudio\FieldTypes\FieldTypeRegistry;
use Flexpik\FilamentStudio\Mcp\Exceptions\IntegrityException;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioFieldOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateField
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function __invoke(string $collectionSlug, array $input, int $tenantId): StudioField
    {
        $registry = app(FieldTypeRegistry::class);
        $knownTypes = $registry->all();

        $data = Validator::validate($input, [
            'column_name' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]*$/', 'max:64'],
            'label' => ['nullable', 'string', 'max:255'],
            'field_type' => ['required', 'string', function ($attr, $value, $fail) use ($knownTypes) {
                if (! array_key_exists($value, $knownTypes)) {
                    $fail("Unknown field type: {$value}");
                }
            }],
            'eav_cast' => ['nullable', 'string', 'in:text,integer,decimal,boolean,datetime,json'],
            'settings' => ['nullable', 'array'],
            'options' => ['nullable', 'array'],
            'options.*.value' => ['required_with:options', 'string'],
            'options.*.label' => ['required_with:options', 'string'],
            'options.*.color' => ['nullable', 'string'],
            'options.*.icon' => ['nullable', 'string'],
            'options.*.sort_order' => ['nullable', 'integer'],
        ]);

        $collection = StudioCollection::query()
            ->forTenant($tenantId)
            ->where('slug', $collectionSlug)
            ->first();

        if ($collection === null) {
            throw new StudioNotFoundException('collection', $collectionSlug);
        }

        $typeClass = $knownTypes[$data['field_type']];
        $eavCast = $data['eav_cast'] ?? $typeClass::$eavCast->value;

        return DB::transaction(function () use ($collection, $data, $eavCast, $tenantId) {
            $exists = StudioField::query()
                ->where('collection_id', $collection->id)
                ->where('column_name', $data['column_name'])
                ->exists();

            if ($exists) {
                throw IntegrityException::duplicate('column_name', $data['column_name']);
            }

            $sortOrder = (int) StudioField::query()
                ->where('collection_id', $collection->id)
                ->max('sort_order');

            $field = StudioField::create([
                'collection_id' => $collection->id,
                'tenant_id' => $tenantId,
                'column_name' => $data['column_name'],
                'label' => $data['label'] ?? Str::headline($data['column_name']),
                'field_type' => $data['field_type'],
                'eav_cast' => $eavCast,
                'sort_order' => $sortOrder + 1,
                'settings' => $data['settings'] ?? [],
            ]);

            foreach ($data['options'] ?? [] as $i => $opt) {
                StudioFieldOption::create([
                    'field_id' => $field->id,
                    'tenant_id' => $tenantId,
                    'value' => $opt['value'],
                    'label' => $opt['label'],
                    'color' => $opt['color'] ?? null,
                    'icon' => $opt['icon'] ?? null,
                    'sort_order' => $opt['sort_order'] ?? $i,
                ]);
            }

            return $field->load('options');
        });
    }
}
