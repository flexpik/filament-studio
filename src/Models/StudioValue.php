<?php

namespace Flexpik\FilamentStudio\Models;

use Flexpik\FilamentStudio\Database\Factories\StudioValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $record_id
 * @property int $field_id
 * @property string|null $val_text
 * @property int|null $val_integer
 * @property float|null $val_decimal
 * @property bool|null $val_boolean
 * @property Carbon|null $val_datetime
 * @property array|null $val_json
 * @property-read StudioRecord $record
 * @property-read StudioField $field
 */
class StudioValue extends Model
{
    /** @use HasFactory<StudioValueFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'values';
    }

    protected function casts(): array
    {
        return [
            'val_integer' => 'integer',
            'val_boolean' => 'boolean',
            'val_datetime' => 'datetime',
            'val_json' => 'array',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(StudioRecord::class, 'record_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(StudioField::class, 'field_id');
    }

    public function resolveValue(): mixed
    {
        return $this->{$this->field->eavColumn()};
    }

    protected static function newFactory(): StudioValueFactory
    {
        return StudioValueFactory::new();
    }
}
