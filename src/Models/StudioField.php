<?php

namespace Flexpik\FilamentStudio\Models;

use Flexpik\FilamentStudio\Database\Factories\StudioFieldFactory;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Enums\FieldWidth;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $collection_id
 * @property int|null $tenant_id
 * @property string $column_name
 * @property string $label
 * @property string $field_type
 * @property EavCast $eav_cast
 * @property bool $is_required
 * @property bool $is_unique
 * @property bool $is_nullable
 * @property bool $is_indexed
 * @property bool $is_system
 * @property string|null $default_value
 * @property string|null $placeholder
 * @property string|null $hint
 * @property string|null $hint_icon
 * @property FieldWidth $width
 * @property int $sort_order
 * @property bool $is_hidden_in_form
 * @property bool $is_hidden_in_table
 * @property bool $is_filterable
 * @property bool $is_disabled_on_create
 * @property bool $is_disabled_on_edit
 * @property bool $is_translatable
 * @property array|null $validation_rules
 * @property array|null $settings
 * @property array|null $translations
 * @property array|null $auto_fill_on
 * @property string|null $auto_fill_value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read StudioCollection $collection
 * @property-read Collection<int, StudioFieldOption> $options
 * @property-read Collection<int, StudioValue> $values
 */
class StudioField extends Model
{
    /** @use HasFactory<StudioFieldFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'fields';
    }

    protected function casts(): array
    {
        return [
            'eav_cast' => EavCast::class,
            'width' => FieldWidth::class,
            'is_required' => 'boolean',
            'is_unique' => 'boolean',
            'is_nullable' => 'boolean',
            'is_indexed' => 'boolean',
            'is_system' => 'boolean',
            'is_hidden_in_form' => 'boolean',
            'is_hidden_in_table' => 'boolean',
            'is_filterable' => 'boolean',
            'is_disabled_on_create' => 'boolean',
            'is_disabled_on_edit' => 'boolean',
            'is_translatable' => 'boolean',
            'validation_rules' => 'array',
            'settings' => 'array',
            'translations' => 'array',
            'auto_fill_on' => 'array',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (StudioField $field) {
            EavQueryBuilder::invalidateFieldCache($field->collection_id);
        });

        static::updated(function (StudioField $field) {
            EavQueryBuilder::invalidateFieldCache($field->collection_id);
        });

        static::deleted(function (StudioField $field) {
            EavQueryBuilder::invalidateFieldCache($field->collection_id);
        });
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(StudioCollection::class, 'collection_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(StudioFieldOption::class, 'field_id')->orderBy('sort_order');
    }

    public function values(): HasMany
    {
        return $this->hasMany(StudioValue::class, 'field_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('is_system')->orderBy('sort_order');
    }

    public function eavColumn(): string
    {
        return $this->eav_cast->column();
    }

    /**
     * Get a field attribute with locale-aware translation from the translations JSON.
     * Falls back to the base attribute value if no translation exists.
     */
    public function getTranslatedAttribute(string $attribute): ?string
    {
        $resolver = app(\Flexpik\FilamentStudio\Services\LocaleResolver::class);

        if (! $resolver->isEnabled()) {
            return $this->{$attribute};
        }

        $locale = $resolver->resolve($this->collection);

        $translations = $this->translations ?? [];
        $attributeTranslations = $translations[$attribute] ?? [];

        if (isset($attributeTranslations[$locale])) {
            return $attributeTranslations[$locale];
        }

        return $this->{$attribute};
    }

    protected static function newFactory(): StudioFieldFactory
    {
        return StudioFieldFactory::new();
    }
}
