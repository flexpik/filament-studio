<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Flows\Requests;

use Flexpik\FilamentStudio\Flows\Enums\LoggingMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->attributes->get('studio_api_key_tenant_id');
        $table = config('filament-studio.table_prefix', 'studio_').'flows';

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_-]+$/',
                Rule::unique($table, 'slug')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
            'logging_mode' => ['nullable', Rule::enum(LoggingMode::class)],
        ];
    }
}
