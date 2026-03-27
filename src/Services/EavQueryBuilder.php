<?php

namespace Flexpik\FilamentStudio\Services;

use Flexpik\FilamentStudio\Enums\AggregateFunction;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Enums\FilterOperator;
use Flexpik\FilamentStudio\Enums\GroupPrecision;
use Flexpik\FilamentStudio\FilamentStudioPlugin;
use Flexpik\FilamentStudio\Filtering\DynamicValueResolver;
use Flexpik\FilamentStudio\Filtering\FilterGroup;
use Flexpik\FilamentStudio\Filtering\FilterNode;
use Flexpik\FilamentStudio\Filtering\FilterRule;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioRecordVersion;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Observers\RecordVersioningObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EavQueryBuilder
{
    protected StudioCollection $collection;

    protected ?int $tenantId = null;

    /** @var array<string> */
    protected array $selectedFields = [];

    /** @var array<array{type: string, field: string, operator: string, value: mixed}> */
    protected array $wheres = [];

    /** @var array<array{field: string, direction: string}> */
    protected array $orderBys = [];

    protected ?FilterGroup $filterTree = null;

    /** @var array<array{field: string, relatedCollection: StudioCollection, displayField: string}> */
    protected array $relations = [];

    /** @var Collection<int, StudioField>|null */
    protected ?Collection $fieldCache = null;

    /** @var array<int, Collection<int, StudioField>> */
    protected static array $staticFieldCache = [];

    protected string $fieldsTable;

    protected string $valuesTable;

    protected string $recordsTable;

    public function __construct(StudioCollection $collection)
    {
        $this->collection = $collection;
        $this->fieldsTable = (new StudioField)->getTable();
        $this->valuesTable = (new StudioValue)->getTable();
        $this->recordsTable = (new StudioRecord)->getTable();
    }

    public static function for(StudioCollection $collection): self
    {
        return new self($collection);
    }

    public function tenant(?int $tenantId): static
    {
        $this->tenantId = $tenantId;

        return $this;
    }

    /**
     * @param  array<string>  $fields
     */
    public function select(array $fields): static
    {
        $this->selectedFields = $fields;

        return $this;
    }

    /**
     * Fetch all matching records with their EAV values.
     *
     * @return Collection<int, \stdClass>
     */
    public function get(): Collection
    {
        FilamentStudioPlugin::applyModifyQuery($this);

        $query = $this->buildBaseQuery();

        // Apply sorting
        foreach ($this->orderBys as $orderBy) {
            $field = $this->resolveField($orderBy['field']);
            if ($field) {
                $cast = $field->eav_cast;
                $query->orderBy(
                    StudioValue::query()
                        ->select($cast->column())
                        ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                        ->where("{$this->valuesTable}.field_id", $field->id)
                        ->limit(1),
                    $orderBy['direction']
                );
            }
        }

        $records = $query
            ->select(["{$this->recordsTable}.id", "{$this->recordsTable}.uuid", "{$this->recordsTable}.created_at", "{$this->recordsTable}.updated_at"])
            ->get();

        if ($records->isEmpty()) {
            return collect();
        }

        $recordIds = $records->pluck('id')->all();
        $values = $this->fetchValues($recordIds);

        return collect($this->assembleResults($records, $values));
    }

    public function paginate(int $perPage = 25, int $page = 0): LengthAwarePaginator
    {
        $page = $page ?: (int) request()->input('page', 1);

        // Step 1: Build base query for record IDs
        $query = $this->buildBaseQuery();

        // Apply sorting
        foreach ($this->orderBys as $orderBy) {
            $field = $this->resolveField($orderBy['field']);
            if ($field) {
                $cast = $field->eav_cast;
                $query->orderBy(
                    StudioValue::query()
                        ->select($cast->column())
                        ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                        ->where("{$this->valuesTable}.field_id", $field->id)
                        ->limit(1),
                    $orderBy['direction']
                );
            }
        }

        // Get total count
        $total = (clone $query)->count();

        // Get paginated record IDs
        $offset = ($page - 1) * $perPage;
        $records = $query
            ->select(["{$this->recordsTable}.id", "{$this->recordsTable}.uuid", "{$this->recordsTable}.created_at", "{$this->recordsTable}.updated_at"])
            ->offset($offset)
            ->limit($perPage)
            ->get();

        if ($records->isEmpty()) {
            return new LengthAwarePaginator([], $total, $perPage, $page);
        }

        // Step 2: Batch-fetch all values for returned record IDs
        $recordIds = $records->pluck('id')->all();
        $values = $this->fetchValues($recordIds);

        // Assemble results
        $items = $this->assembleResults($records, $values);

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    protected function buildBaseQuery(): Builder
    {
        $query = StudioRecord::query()
            ->where("{$this->recordsTable}.collection_id", $this->collection->id)
            ->whereNull("{$this->recordsTable}.deleted_at");

        if ($this->tenantId !== null) {
            $query->where("{$this->recordsTable}.tenant_id", $this->tenantId);
        }

        // Apply where clauses
        foreach ($this->wheres as $where) {
            $this->applyWhere($query, $where);
        }

        // Apply filter tree (advanced filtering)
        if ($this->filterTree !== null) {
            $this->applyFilterNode($query, $this->filterTree);
        }

        return $query;
    }

    /**
     * @param  array<int>  $recordIds
     */
    protected function fetchValues(array $recordIds): Collection
    {
        $query = StudioValue::query()
            ->join($this->fieldsTable, "{$this->fieldsTable}.id", '=', "{$this->valuesTable}.field_id")
            ->whereIn("{$this->valuesTable}.record_id", $recordIds)
            ->select([
                "{$this->valuesTable}.record_id",
                "{$this->fieldsTable}.column_name",
                "{$this->fieldsTable}.eav_cast",
                "{$this->valuesTable}.val_text",
                "{$this->valuesTable}.val_integer",
                "{$this->valuesTable}.val_decimal",
                "{$this->valuesTable}.val_boolean",
                "{$this->valuesTable}.val_datetime",
                "{$this->valuesTable}.val_json",
            ]);

        // Filter by selected fields if specified
        if (! empty($this->selectedFields)) {
            $query->whereIn("{$this->fieldsTable}.column_name", $this->selectedFields);
        }

        return $query->get()->groupBy('record_id');
    }

    /**
     * @return array<\stdClass>
     */
    protected function assembleResults(Collection $records, Collection $groupedValues): array
    {
        $items = $records->map(function ($record) use ($groupedValues) {
            $obj = new \stdClass;
            $obj->id = $record->id;
            $obj->uuid = $record->uuid;
            $obj->created_at = $record->created_at;
            $obj->updated_at = $record->updated_at;

            $recordValues = $groupedValues->get($record->id, collect());

            foreach ($recordValues as $value) {
                $cast = EavCast::from($value->eav_cast);
                $columnName = $value->column_name;
                $obj->{$columnName} = $this->castValue($value, $cast);
            }

            return $obj;
        })->all();

        // Resolve relations
        foreach ($this->relations as $relation) {
            $items = $this->resolveRelation($items, $relation);
        }

        return $items;
    }

    protected function castValue(object $value, EavCast $cast): mixed
    {
        return match ($cast) {
            EavCast::Text => $value->val_text,
            EavCast::Integer => $value->val_integer !== null ? (int) $value->val_integer : null,
            EavCast::Decimal => $value->val_decimal !== null ? (float) $value->val_decimal : null,
            EavCast::Boolean => $value->val_boolean !== null ? (bool) $value->val_boolean : null,
            EavCast::Datetime => $value->val_datetime,
            EavCast::Json => $value->val_json !== null ? (is_string($value->val_json) ? json_decode($value->val_json, true) : $value->val_json) : null,
        };
    }

    protected function resolveField(string $columnName): ?StudioField
    {
        return $this->getFields()->firstWhere('column_name', $columnName);
    }

    /**
     * @return Collection<int, StudioField>
     */
    protected function getFields(): Collection
    {
        if ($this->fieldCache === null) {
            $this->fieldCache = static::getCachedFields($this->collection);
        }

        return $this->fieldCache;
    }

    /**
     * Get cached fields for a collection (request-scoped via static property).
     */
    public static function getCachedFields(StudioCollection $collection, bool $forceRefresh = false): Collection
    {
        if ($forceRefresh || ! isset(static::$staticFieldCache[$collection->id])) {
            static::$staticFieldCache[$collection->id] = StudioField::query()
                ->where('collection_id', $collection->id)
                ->ordered()
                ->get();
        }

        return static::$staticFieldCache[$collection->id];
    }

    /**
     * Invalidate the cached fields for a collection.
     */
    public static function invalidateFieldCache(?int $collectionId = null): void
    {
        if ($collectionId !== null) {
            unset(static::$staticFieldCache[$collectionId]);
        } else {
            static::$staticFieldCache = [];
        }
    }

    protected function applyWhere(Builder $query, array $where): void
    {
        $field = $this->resolveField($where['field']);
        if (! $field) {
            return;
        }

        $cast = $field->eav_cast;
        $column = $cast->column();

        match ($where['type']) {
            'basic' => $query->whereExists(function ($sub) use ($field, $column, $where) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->join($this->fieldsTable, "{$this->fieldsTable}.id", '=', "{$this->valuesTable}.field_id")
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->where("{$this->valuesTable}.{$column}", $where['operator'], $where['value']);
            }),
            'in' => $query->whereExists(function ($sub) use ($field, $column, $where) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->whereIn("{$this->valuesTable}.{$column}", $where['value']);
            }),
            'between' => $query->whereExists(function ($sub) use ($field, $column, $where) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->whereBetween("{$this->valuesTable}.{$column}", $where['value']);
            }),
            'null' => $query->whereNotExists(function ($sub) use ($field, $column) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->whereNotNull("{$this->valuesTable}.{$column}");
            }),
            'notNull' => $query->whereExists(function ($sub) use ($field, $column) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->whereNotNull("{$this->valuesTable}.{$column}");
            }),
            'referencing' => $query->whereExists(function ($sub) use ($field, $where) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->where("{$this->valuesTable}.val_text", $where['value']);
            }),
            'search' => $query->where(function ($q) use ($where) {
                foreach ((array) $where['fields'] as $searchField) {
                    $f = $this->resolveField($searchField);
                    if ($f) {
                        $q->orWhereExists(function ($sub) use ($f, $where) {
                            $sub->select(DB::raw(1))
                                ->from($this->valuesTable)
                                ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                                ->where("{$this->valuesTable}.field_id", $f->id)
                                ->where("{$this->valuesTable}.val_text", 'LIKE', "%{$where['value']}%");
                        });
                    }
                }
            }),
            default => null,
        };
    }

    public function applyFilterTree(FilterGroup $tree): static
    {
        if ($tree->isEmpty()) {
            return $this;
        }

        $this->filterTree = $tree;

        return $this;
    }

    /**
     * Apply the stored filter tree to an external query builder.
     * Used for integrating with Filament's modifyQueryUsing().
     */
    public function applyFilterToQuery(Builder $query): void
    {
        if ($this->filterTree !== null) {
            $this->applyFilterNode($query, $this->filterTree);
        }
    }

    protected function applyFilterNode(Builder $query, FilterNode $node): void
    {
        if ($node instanceof FilterRule) {
            $this->applyFilterRule($query, $node);

            return;
        }

        if ($node instanceof FilterGroup) {
            $query->where(function (Builder $q) use ($node) {
                foreach ($node->children as $child) {
                    if ($node->logic === 'or') {
                        $q->orWhere(function (Builder $inner) use ($child) {
                            $this->applyFilterNode($inner, $child);
                        });
                    } else {
                        $q->where(function (Builder $inner) use ($child) {
                            $this->applyFilterNode($inner, $child);
                        });
                    }
                }
            });
        }
    }

    protected function applyFilterRule(Builder $query, FilterRule $rule): void
    {
        if ($rule->isRelational()) {
            $this->applyRelationalFilterRule($query, $rule);

            return;
        }

        $field = $this->resolveField($rule->field);
        if (! $field) {
            return;
        }

        $cast = $field->eav_cast;
        $column = $cast->column();
        $value = DynamicValueResolver::resolve($rule->value);
        $operator = $rule->operator;

        match (true) {
            $operator === FilterOperator::IsNull => $query->whereNotExists(function ($sub) use ($field, $column) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->whereNotNull("{$this->valuesTable}.{$column}");
            }),

            $operator === FilterOperator::IsNotNull => $query->whereExists(function ($sub) use ($field, $column) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->whereNotNull("{$this->valuesTable}.{$column}");
            }),

            $operator === FilterOperator::IsEmpty => $query->whereNotExists(function ($sub) use ($field, $column) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->whereNotNull("{$this->valuesTable}.{$column}")
                    ->where("{$this->valuesTable}.{$column}", '!=', '');
            }),

            $operator === FilterOperator::IsNotEmpty => $query->whereExists(function ($sub) use ($field, $column) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->whereNotNull("{$this->valuesTable}.{$column}")
                    ->where("{$this->valuesTable}.{$column}", '!=', '');
            }),

            $operator === FilterOperator::IsTrue => $query->whereExists(function ($sub) use ($field) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->where("{$this->valuesTable}.val_boolean", true);
            }),

            $operator === FilterOperator::IsFalse => $query->whereExists(function ($sub) use ($field) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->where("{$this->valuesTable}.val_boolean", false);
            }),

            $operator === FilterOperator::Contains => $query->whereExists(function ($sub) use ($field, $column, $value) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->where("{$this->valuesTable}.{$column}", 'LIKE', "%{$value}%");
            }),

            $operator === FilterOperator::NotContains => $query->whereNotExists(function ($sub) use ($field, $column, $value) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->where("{$this->valuesTable}.{$column}", 'LIKE', "%{$value}%");
            }),

            $operator === FilterOperator::StartsWith => $query->whereExists(function ($sub) use ($field, $column, $value) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->where("{$this->valuesTable}.{$column}", 'LIKE', "{$value}%");
            }),

            $operator === FilterOperator::EndsWith => $query->whereExists(function ($sub) use ($field, $column, $value) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->where("{$this->valuesTable}.{$column}", 'LIKE', "%{$value}");
            }),

            $operator === FilterOperator::In => $query->whereExists(function ($sub) use ($field, $column, $value) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->whereIn("{$this->valuesTable}.{$column}", (array) $value);
            }),

            $operator === FilterOperator::NotIn => $query->whereNotExists(function ($sub) use ($field, $column, $value) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->whereIn("{$this->valuesTable}.{$column}", (array) $value);
            }),

            $operator === FilterOperator::Between => $query->whereExists(function ($sub) use ($field, $column, $value) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->whereBetween("{$this->valuesTable}.{$column}", (array) $value);
            }),

            $operator === FilterOperator::NotBetween => $query->whereNotExists(function ($sub) use ($field, $column, $value) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->whereBetween("{$this->valuesTable}.{$column}", (array) $value);
            }),

            $operator === FilterOperator::ContainsAny => $query->whereExists(function ($sub) use ($field, $value) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->where(function ($q) use ($value) {
                        foreach ((array) $value as $v) {
                            $q->orWhereJsonContains("{$this->valuesTable}.val_json", $v);
                        }
                    });
            }),

            $operator === FilterOperator::ContainsAll => $query->whereExists(function ($sub) use ($field, $value) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->where(function ($q) use ($value) {
                        foreach ((array) $value as $v) {
                            $q->whereJsonContains("{$this->valuesTable}.val_json", $v);
                        }
                    });
            }),

            $operator === FilterOperator::ContainsNone => $query->whereNotExists(function ($sub) use ($field, $value) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->where(function ($q) use ($value) {
                        foreach ((array) $value as $v) {
                            $q->orWhereJsonContains("{$this->valuesTable}.val_json", $v);
                        }
                    });
            }),

            default => $query->whereExists(function ($sub) use ($field, $column, $operator, $value) {
                $sub->select(DB::raw(1))
                    ->from($this->valuesTable)
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->where("{$this->valuesTable}.{$column}", $operator->toSql(), $value);
            }),
        };
    }

    protected function applyRelationalFilterRule(Builder $query, FilterRule $rule): void
    {
        $field = $this->resolveField($rule->field);
        if (! $field) {
            return;
        }

        $relatedCollectionSlug = $field->settings['related_collection'] ?? null;
        if (! $relatedCollectionSlug) {
            return;
        }

        $relatedCollection = StudioCollection::where('slug', $relatedCollectionSlug)->first();
        if (! $relatedCollection) {
            return;
        }

        $relatedTree = FilterGroup::fromArray([
            'logic' => 'and',
            'rules' => [
                [
                    'field' => $rule->relatedField,
                    'operator' => $rule->operator->value,
                    'value' => $rule->value,
                ],
            ],
        ]);

        $matchingUuids = static::for($relatedCollection)
            ->applyFilterTree($relatedTree)
            ->get()
            ->pluck('uuid')
            ->all();

        if (empty($matchingUuids)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereExists(function ($sub) use ($field, $matchingUuids) {
            $sub->select(DB::raw(1))
                ->from($this->valuesTable)
                ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                ->where("{$this->valuesTable}.field_id", $field->id)
                ->whereIn("{$this->valuesTable}.val_text", $matchingUuids);
        });
    }

    public function where(string $field, mixed $operatorOrValue = null, mixed $value = null): static
    {
        if ($value === null) {
            $value = $operatorOrValue;
            $operator = '=';
        } else {
            $operator = $operatorOrValue;
        }

        $this->wheres[] = [
            'type' => 'basic',
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * @param  array<mixed>  $values
     */
    public function whereIn(string $field, array $values): static
    {
        $this->wheres[] = [
            'type' => 'in',
            'field' => $field,
            'operator' => 'in',
            'value' => $values,
        ];

        return $this;
    }

    /**
     * @param  array{0: mixed, 1: mixed}  $values
     */
    public function whereBetween(string $field, array $values): static
    {
        $this->wheres[] = [
            'type' => 'between',
            'field' => $field,
            'operator' => 'between',
            'value' => $values,
        ];

        return $this;
    }

    public function whereDate(string $field, string $operator, mixed $value): static
    {
        $this->wheres[] = [
            'type' => 'basic',
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    public function whereNull(string $field): static
    {
        $this->wheres[] = [
            'type' => 'null',
            'field' => $field,
            'operator' => 'null',
            'value' => null,
        ];

        return $this;
    }

    public function whereNotNull(string $field): static
    {
        $this->wheres[] = [
            'type' => 'notNull',
            'field' => $field,
            'operator' => 'notNull',
            'value' => null,
        ];

        return $this;
    }

    /**
     * @param  array<string>  $fields
     */
    public function search(string $term, array $fields): static
    {
        $this->wheres[] = [
            'type' => 'search',
            'field' => $fields[0] ?? '',
            'fields' => $fields,
            'operator' => 'like',
            'value' => $term,
        ];

        return $this;
    }

    public function orderBy(string $field, string $direction = 'asc'): static
    {
        $this->orderBys[] = [
            'field' => $field,
            'direction' => strtolower($direction) === 'desc' ? 'desc' : 'asc',
        ];

        return $this;
    }

    /**
     * Return the base Eloquent query for Filament table integration.
     * Adds subquery selects for each field so EAV values appear as
     * virtual attributes on the model (e.g., $record->name).
     */
    public function toEloquentQuery(): Builder
    {
        $query = $this->buildBaseQuery();

        // Add subquery selects for each field so values are accessible as attributes
        foreach ($this->getFields() as $field) {
            $column = $field->eav_cast->column();
            $query->addSelect([
                $field->column_name => StudioValue::query()
                    ->select("{$this->valuesTable}.{$column}")
                    ->whereColumn("{$this->valuesTable}.record_id", "{$this->recordsTable}.id")
                    ->where("{$this->valuesTable}.field_id", $field->id)
                    ->limit(1),
            ]);
        }

        return $query;
    }

    /**
     * Get a flat array of column_name => value for a given record.
     *
     * @return array<string, mixed>
     */
    public function getRecordData(StudioRecord $record): array
    {
        $values = StudioValue::query()
            ->join($this->fieldsTable, "{$this->fieldsTable}.id", '=', "{$this->valuesTable}.field_id")
            ->where("{$this->valuesTable}.record_id", $record->id)
            ->select([
                "{$this->fieldsTable}.column_name",
                "{$this->fieldsTable}.eav_cast",
                "{$this->valuesTable}.val_text",
                "{$this->valuesTable}.val_integer",
                "{$this->valuesTable}.val_decimal",
                "{$this->valuesTable}.val_boolean",
                "{$this->valuesTable}.val_datetime",
                "{$this->valuesTable}.val_json",
            ])
            ->get();

        $data = [];
        /** @var object{eav_cast: string, column_name: string, val_text: mixed, val_integer: mixed, val_decimal: mixed, val_boolean: mixed, val_datetime: mixed, val_json: mixed} $value */
        foreach ($values as $value) {
            $cast = EavCast::from($value->eav_cast);
            $data[$value->column_name] = $this->castValue($value, $cast);
        }

        return $data;
    }

    public function create(array $data, ?int $userId = null): StudioRecord
    {
        return DB::transaction(function () use ($data, $userId) {
            $record = StudioRecord::create([
                'uuid' => (string) Str::uuid(),
                'collection_id' => $this->collection->id,
                'tenant_id' => $this->tenantId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->bulkInsertValues($record->id, $data);

            return $record;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $recordId, array $data, ?int $userId = null): void
    {
        DB::transaction(function () use ($recordId, $data, $userId) {
            $record = StudioRecord::findOrFail($recordId);

            if ($this->collection->enable_versioning) {
                $observer = new RecordVersioningObserver;
                $observer->updating($record);
            }

            if ($userId !== null) {
                $record->updateQuietly(['updated_by' => $userId]);
            } else {
                $record->touchQuietly();
            }

            foreach ($data as $columnName => $value) {
                $field = $this->resolveField($columnName);
                if (! $field) {
                    continue;
                }

                $cast = $field->eav_cast;
                $column = $cast->column();

                // Reset all val_* columns to null, then set the correct one
                $updateData = [
                    'val_text' => null,
                    'val_integer' => null,
                    'val_decimal' => null,
                    'val_boolean' => null,
                    'val_datetime' => null,
                    'val_json' => null,
                ];
                $updateData[$column] = $this->prepareValueForStorage($value, $cast);

                StudioValue::updateOrCreate(
                    [
                        'record_id' => $recordId,
                        'field_id' => $field->id,
                    ],
                    $updateData
                );
            }

            if ($this->collection->enable_versioning) {
                $observer = new RecordVersioningObserver;
                $observer->updated($record);
            }
        });
    }

    public function delete(int $recordId): void
    {
        $record = StudioRecord::findOrFail($recordId);

        if ($this->collection->enable_soft_deletes) {
            $record->update(['deleted_at' => now()]);
        } else {
            DB::transaction(function () use ($record, $recordId) {
                StudioValue::where('record_id', $recordId)->delete();
                $record->delete();
            });
        }
    }

    /**
     * Restore a record's values from a version snapshot.
     * Creates a new version entry before restoring (the restore itself is versioned).
     */
    public function restoreFromVersion(string $uuid, int $versionId): void
    {
        /** @var StudioRecord $record */
        $record = $this->buildBaseQuery()->where('uuid', $uuid)->firstOrFail();

        /** @var StudioRecordVersion $version */
        $version = StudioRecordVersion::where('id', $versionId)
            ->where('record_id', $record->id)
            ->firstOrFail();

        $snapshot = $version->snapshot;
        /** @var \Illuminate\Database\Eloquent\Collection<int, StudioField> $fields */
        $fields = StudioField::where('collection_id', $this->collection->id)->get()->keyBy('column_name');

        // Snapshot the current state before restoring (so the restore itself is versioned)
        if ($this->collection->enable_versioning) {
            $observer = new RecordVersioningObserver;
            $observer->updating($record);
        }

        // Write the snapshot values back
        foreach ($snapshot as $columnName => $value) {
            /** @var StudioField|null $field */
            $field = $fields->get($columnName);
            if (! $field) {
                continue;
            }

            $eavColumn = $field->eavColumn();

            StudioValue::updateOrCreate(
                ['record_id' => $record->id, 'field_id' => $field->id],
                [$eavColumn => $value]
            );
        }

        $record->touch();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function bulkInsertValues(int $recordId, array $data): void
    {
        $inserts = [];

        foreach ($data as $columnName => $value) {
            $field = $this->resolveField($columnName);
            if (! $field) {
                continue;
            }

            $cast = $field->eav_cast;
            $column = $cast->column();

            $row = [
                'record_id' => $recordId,
                'field_id' => $field->id,
                'val_text' => null,
                'val_integer' => null,
                'val_decimal' => null,
                'val_boolean' => null,
                'val_datetime' => null,
                'val_json' => null,
            ];
            $row[$column] = $this->prepareValueForStorage($value, $cast);

            $inserts[] = $row;
        }

        if (! empty($inserts)) {
            StudioValue::insert($inserts);
        }
    }

    protected function prepareValueForStorage(mixed $value, EavCast $cast): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($cast) {
            EavCast::Text => (string) $value,
            EavCast::Integer => (int) $value,
            EavCast::Decimal => (float) $value,
            EavCast::Boolean => (bool) $value ? 1 : 0,
            EavCast::Datetime => $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : (string) $value,
            EavCast::Json => is_string($value) ? $value : json_encode($value),
        };
    }

    public function withRelated(string $field, StudioCollection $relatedCollection, string $displayField): static
    {
        $this->relations[] = [
            'field' => $field,
            'relatedCollection' => $relatedCollection,
            'displayField' => $displayField,
        ];

        return $this;
    }

    /**
     * @param  array<\stdClass>  $items
     * @param  array{field: string, relatedCollection: StudioCollection, displayField: string}  $relation
     * @return array<\stdClass>
     */
    protected function resolveRelation(array $items, array $relation): array
    {
        $fieldName = $relation['field'];
        $relatedCollection = $relation['relatedCollection'];
        $displayField = $relation['displayField'];
        $displayProperty = "{$fieldName}_display";

        // Collect all foreign UUIDs from items (handles both single UUIDs and arrays)
        $foreignUuids = [];
        foreach ($items as $item) {
            $value = $item->{$fieldName} ?? null;
            if (is_array($value)) {
                foreach ($value as $uuid) {
                    $foreignUuids[] = $uuid;
                }
            } elseif ($value !== null) {
                $foreignUuids[] = $value;
            }
        }

        $foreignUuids = array_unique(array_filter($foreignUuids));

        if (empty($foreignUuids)) {
            foreach ($items as $item) {
                $item->{$displayProperty} = is_array($item->{$fieldName} ?? null) ? [] : null;
            }

            return $items;
        }

        // Batch-fetch related records by UUID
        $relatedRecords = StudioRecord::query()
            ->where('collection_id', $relatedCollection->id)
            ->whereIn('uuid', $foreignUuids)
            ->pluck('id', 'uuid');

        // Fetch display values for those records
        $displayFieldModel = StudioField::query()
            ->where('collection_id', $relatedCollection->id)
            ->where('column_name', $displayField)
            ->first();

        $displayValues = collect();
        if ($displayFieldModel && $relatedRecords->isNotEmpty()) {
            $displayValues = StudioValue::query()
                ->where('field_id', $displayFieldModel->id)
                ->whereIn('record_id', $relatedRecords->values())
                ->get()
                ->keyBy('record_id');
        }

        // Map UUID -> display value
        $uuidToDisplay = [];
        foreach ($relatedRecords as $uuid => $recordId) {
            $val = $displayValues->get($recordId);
            if ($val && $displayFieldModel) {
                $cast = $displayFieldModel->eav_cast;
                $uuidToDisplay[$uuid] = $this->castValue($val, $cast);
            } else {
                $uuidToDisplay[$uuid] = null;
            }
        }

        // Set display property on each item (handle both single and array values)
        foreach ($items as $item) {
            $value = $item->{$fieldName} ?? null;
            if (is_array($value)) {
                $item->{$displayProperty} = collect($value)
                    ->map(fn ($uuid) => $uuidToDisplay[$uuid] ?? null)
                    ->filter()
                    ->values()
                    ->all();
            } else {
                $item->{$displayProperty} = $uuidToDisplay[$value] ?? null;
            }
        }

        return $items;
    }

    /**
     * Filter records where a belongs_to field references a specific UUID.
     * Used for has_many reverse lookups.
     */
    public function whereReferencing(string $fieldName, string $uuid): static
    {
        $this->wheres[] = [
            'type' => 'referencing',
            'field' => $fieldName,
            'operator' => '=',
            'value' => $uuid,
        ];

        return $this;
    }

    /**
     * Delete a record while enforcing referential integrity.
     * Checks all belongs_to fields across all collections that reference this collection.
     *
     * @throws \RuntimeException When on_delete=restrict and references exist
     */
    public function deleteWithIntegrity(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            /** @var StudioRecord $record */
            $record = $this->buildBaseQuery()->where('uuid', $uuid)->firstOrFail();

            // Find all belongs_to fields that reference this collection's slug
            $referencingFields = StudioField::query()
                ->where('field_type', 'belongs_to')
                ->whereJsonContains('settings->related_collection', $this->collection->slug)
                ->get();

            foreach ($referencingFields as $field) {
                $onDelete = $field->settings['on_delete'] ?? 'restrict';

                $referencingValues = StudioValue::query()
                    ->where('field_id', $field->id)
                    ->where('val_text', $record->uuid)
                    ->get();

                if ($referencingValues->isEmpty()) {
                    continue;
                }

                if ($onDelete === 'restrict') {
                    throw new \RuntimeException('Cannot delete record: it is referenced by other records');
                }

                if ($onDelete === 'set_null') {
                    StudioValue::query()
                        ->where('field_id', $field->id)
                        ->where('val_text', $record->uuid)
                        ->update(['val_text' => null]);
                }
            }

            // Handle belongs_to_many references (remove UUID from JSON arrays)
            $referencingManyFields = StudioField::query()
                ->where('field_type', 'belongs_to_many')
                ->whereJsonContains('settings->related_collection', $this->collection->slug)
                ->get();

            foreach ($referencingManyFields as $field) {
                $values = StudioValue::query()
                    ->where('field_id', $field->id)
                    ->whereJsonContains('val_json', $record->uuid)
                    ->get();

                foreach ($values as $value) {
                    $array = $value->val_json ?? [];
                    $array = array_values(array_filter($array, fn ($u) => $u !== $record->uuid));
                    $value->update(['val_json' => $array]);
                }
            }

            // Delete the record's values and the record itself
            StudioValue::where('record_id', $record->id)->delete();
            $record->forceDelete();
        });
    }

    /**
     * @return Collection<int|string, mixed>
     */
    public function pluck(string $valueField, ?string $keyField = null): Collection
    {
        $query = $this->buildBaseQuery();
        $records = $query->select(["{$this->recordsTable}.id", "{$this->recordsTable}.uuid"])->get();

        if ($records->isEmpty()) {
            return collect();
        }

        $recordIds = $records->pluck('id')->all();

        // Fetch value field
        $valueFieldModel = $this->resolveField($valueField);
        if (! $valueFieldModel) {
            return collect();
        }

        $valueCast = $valueFieldModel->eav_cast;
        $values = StudioValue::query()
            ->where('field_id', $valueFieldModel->id)
            ->whereIn('record_id', $recordIds)
            ->get()
            ->keyBy('record_id');

        // Build result
        $result = [];
        /** @var StudioRecord $record */
        foreach ($records as $record) {
            $val = $values->get($record->id);
            $displayValue = $val ? $this->castValue($val, $valueCast) : null;

            if ($keyField === 'uuid') {
                $result[$record->uuid] = $displayValue;
            } elseif ($keyField === null) {
                $result[] = $displayValue;
            } else {
                // keyField is another EAV field — need to fetch it
                $keyFieldModel = $this->resolveField($keyField);
                if ($keyFieldModel) {
                    // This case requires fetching key values too
                    $result[$record->id] = $displayValue;
                } else {
                    $result[] = $displayValue;
                }
            }
        }

        // If keyField is a non-uuid EAV field, re-key by that field's values
        if ($keyField !== null && $keyField !== 'uuid') {
            $keyFieldModel = $this->resolveField($keyField);
            if ($keyFieldModel) {
                $keyCast = $keyFieldModel->eav_cast;
                $keyValues = StudioValue::query()
                    ->where('field_id', $keyFieldModel->id)
                    ->whereIn('record_id', $recordIds)
                    ->get()
                    ->keyBy('record_id');

                $result = [];
                /** @var StudioRecord $record */
                foreach ($records as $record) {
                    $keyVal = $keyValues->get($record->id);
                    $key = $keyVal ? $this->castValue($keyVal, $keyCast) : $record->id;
                    $val = $values->get($record->id);
                    $result[$key] = $val ? $this->castValue($val, $valueCast) : null;
                }
            }
        }

        return collect($result);
    }

    /**
     * Run a single aggregate function on a field across all matching records.
     *
     * @return int|float|string|null
     */
    public function aggregate(AggregateFunction $function, ?string $fieldName = null): mixed
    {
        $query = $this->buildBaseQuery();

        if ($fieldName === null && $function === AggregateFunction::Count) {
            return $query->count();
        }

        $field = $this->resolveField($fieldName);
        if (! $field) {
            return null;
        }

        $column = $field->eav_cast->column();

        $result = DB::table($this->valuesTable)
            ->whereIn('record_id', $query->select("{$this->recordsTable}.id"))
            ->where('field_id', $field->id)
            ->whereNotNull($column)
            ->selectRaw($function->toSql($column).' as agg_result')
            ->value('agg_result');

        return $result;
    }

    /**
     * Run an aggregate function grouped by a datetime field at a given precision.
     *
     * @return Collection<string, int|float> Keyed by formatted date string.
     */
    public function aggregateTimeSeries(
        AggregateFunction $function,
        string $valueFieldName,
        string $dateFieldName,
        GroupPrecision $precision,
    ): Collection {
        $dateField = $this->resolveField($dateFieldName);
        $valueField = $this->resolveField($valueFieldName);

        if (! $dateField || ! $valueField) {
            return collect();
        }

        $dateColumn = $dateField->eav_cast->column();
        $valueColumn = $valueField->eav_cast->column();
        $recordIds = $this->buildBaseQuery()->select("{$this->recordsTable}.id");

        $dateAlias = 'dv';
        $valueAlias = 'vv';

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $periodExpression = "strftime('{$precision->sqliteFormat()}', {$dateAlias}.{$dateColumn})";
            $periodBindings = [];
        } elseif ($driver === 'pgsql') {
            $periodExpression = "TO_CHAR(DATE_TRUNC('{$precision->postgresqlTrunc()}', {$dateAlias}.{$dateColumn}), 'YYYY-MM-DD HH24:00')";
            $periodBindings = [];
        } else {
            $periodExpression = "DATE_FORMAT({$dateAlias}.{$dateColumn}, ?)";
            $periodBindings = [$precision->mysqlFormat()];
        }

        $rows = DB::table("{$this->valuesTable} as {$dateAlias}")
            ->join(
                "{$this->valuesTable} as {$valueAlias}",
                fn ($join) => $join
                    ->on("{$dateAlias}.record_id", '=', "{$valueAlias}.record_id")
                    ->where("{$valueAlias}.field_id", $valueField->id)
            )
            ->whereIn("{$dateAlias}.record_id", $recordIds)
            ->where("{$dateAlias}.field_id", $dateField->id)
            ->whereNotNull("{$dateAlias}.{$dateColumn}")
            ->whereNotNull("{$valueAlias}.{$valueColumn}")
            ->selectRaw("{$periodExpression} as period", $periodBindings)
            ->selectRaw($function->toSql("{$valueAlias}.{$valueColumn}").' as agg_result')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $rows->pluck('agg_result', 'period');
    }

    /**
     * Run an aggregate function grouped by a categorical field.
     *
     * @return Collection<string, int|float> Keyed by the group field's value.
     */
    public function aggregateByGroup(
        AggregateFunction $function,
        string $valueFieldName,
        string $groupFieldName,
    ): Collection {
        $groupField = $this->resolveField($groupFieldName);
        $valueField = $this->resolveField($valueFieldName);

        if (! $groupField || ! $valueField) {
            return collect();
        }

        $groupColumn = $groupField->eav_cast->column();
        $valueColumn = $valueField->eav_cast->column();
        $recordIds = $this->buildBaseQuery()->select("{$this->recordsTable}.id");

        $groupAlias = 'gv';
        $valueAlias = 'vv';

        $rows = DB::table("{$this->valuesTable} as {$groupAlias}")
            ->join(
                "{$this->valuesTable} as {$valueAlias}",
                fn ($join) => $join
                    ->on("{$groupAlias}.record_id", '=', "{$valueAlias}.record_id")
                    ->where("{$valueAlias}.field_id", $valueField->id)
            )
            ->whereIn("{$groupAlias}.record_id", $recordIds)
            ->where("{$groupAlias}.field_id", $groupField->id)
            ->whereNotNull("{$groupAlias}.{$groupColumn}")
            ->select("{$groupAlias}.{$groupColumn} as group_key")
            ->selectRaw($function->toSql("{$valueAlias}.{$valueColumn}").' as agg_result')
            ->groupBy('group_key')
            ->orderBy('group_key')
            ->get();

        return $rows->pluck('agg_result', 'group_key');
    }
}
