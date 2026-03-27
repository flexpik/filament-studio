<?php

namespace Flexpik\FilamentStudio\Models;

use Flexpik\FilamentStudio\Database\Factories\StudioApiKeyFactory;
use Flexpik\FilamentStudio\Enums\ApiAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property string $name
 * @property string $key
 * @property array|null $permissions
 * @property bool $is_active
 * @property Carbon|null $last_used_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StudioApiKey extends Model
{
    /** @use HasFactory<StudioApiKeyFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'api_keys';
    }

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function can(string $collectionSlug, ApiAction $action): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        $permissions = $this->permissions ?? [];

        if (isset($permissions[$collectionSlug]) && in_array($action->value, $permissions[$collectionSlug], true)) {
            return true;
        }

        if (isset($permissions['*']) && in_array($action->value, $permissions['*'], true)) {
            return true;
        }

        return false;
    }

    public static function findByKey(string $plainKey): ?self
    {
        return static::query()
            ->where('key', hash('sha256', $plainKey))
            ->where('is_active', true)
            ->first();
    }

    public function scopeForTenant($query, ?int $tenantId)
    {
        if ($tenantId === null) {
            return $query;
        }

        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function touchLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    protected static function newFactory(): StudioApiKeyFactory
    {
        return StudioApiKeyFactory::new();
    }
}
