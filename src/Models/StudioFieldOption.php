<?php

namespace Flexpik\FilamentStudio\Models;

use Flexpik\FilamentStudio\Database\Factories\StudioFieldOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $field_id
 * @property int|null $tenant_id
 * @property string $value
 * @property string $label
 * @property string|null $color
 * @property string|null $icon
 * @property int $sort_order
 * @property-read StudioField $field
 */
class StudioFieldOption extends Model
{
    /** @use HasFactory<StudioFieldOptionFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'field_options';
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(StudioField::class, 'field_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    protected static function newFactory(): StudioFieldOptionFactory
    {
        return StudioFieldOptionFactory::new();
    }
}
