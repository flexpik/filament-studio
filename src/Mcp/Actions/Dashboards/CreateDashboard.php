<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Dashboards;

use Flexpik\FilamentStudio\Mcp\Exceptions\IntegrityException;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateDashboard
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function __invoke(array $input, ?int $tenantId): StudioDashboard
    {
        $data = Validator::validate($input, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'regex:/^[a-z][a-z0-9-]*$/', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
            'auto_refresh_interval' => ['nullable', 'integer', 'min:5'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $slug = $data['slug'] ?? Str::slug($data['name']);

        try {
            return StudioDashboard::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'slug' => $slug,
                'icon' => $data['icon'] ?? null,
                'color' => $data['color'] ?? null,
                'auto_refresh_interval' => $data['auto_refresh_interval'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Duplicate')) {
                throw IntegrityException::duplicate('slug', $slug);
            }
            throw $e;
        }
    }
}
