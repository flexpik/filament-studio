<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Dashboards;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Illuminate\Support\Facades\Validator;

class UpdateDashboard
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function __invoke(array $input, ?int $tenantId): StudioDashboard
    {
        $data = Validator::validate($input, [
            'slug' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
            'auto_refresh_interval' => ['nullable', 'integer', 'min:5'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $dash = StudioDashboard::query()->forTenant($tenantId)->where('slug', $data['slug'])->first();
        if ($dash === null) {
            throw new StudioNotFoundException('dashboard', $data['slug']);
        }

        $dash->fill(array_filter(
            array_intersect_key($data, array_flip(['name', 'icon', 'color', 'auto_refresh_interval', 'sort_order'])),
            fn ($v) => $v !== null,
        ));
        $dash->save();

        return $dash;
    }
}
