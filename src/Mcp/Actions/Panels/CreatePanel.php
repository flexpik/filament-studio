<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Panels;

use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Panels\PanelTypeRegistry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreatePanel
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function __invoke(array $input, ?int $tenantId): StudioPanel
    {
        $panelKeys = array_keys(app(PanelTypeRegistry::class)->all());

        $data = Validator::validate($input, [
            'dashboard_slug' => ['required', 'string'],
            'panel_type' => ['required', 'string', Rule::in($panelKeys)],
            'placement' => ['required', 'string', Rule::in(array_map(fn ($c) => $c->value, PanelPlacement::cases()))],
            'context_collection_id' => ['nullable', 'integer'],
            'grid' => ['nullable', 'array'],
            'grid.col_span' => ['nullable', 'integer', 'min:1', 'max:12'],
            'grid.row_span' => ['nullable', 'integer', 'min:1', 'max:12'],
            'grid.order' => ['nullable', 'integer'],
            'header' => ['nullable', 'array'],
            'header.visible' => ['nullable', 'boolean'],
            'header.label' => ['nullable', 'string'],
            'header.icon' => ['nullable', 'string'],
            'header.color' => ['nullable', 'string'],
            'header.note' => ['nullable', 'string'],
            'config' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $dashboard = StudioDashboard::query()->forTenant($tenantId)->where('slug', $data['dashboard_slug'])->first();

        if ($dashboard === null) {
            throw new StudioNotFoundException('dashboard', $data['dashboard_slug']);
        }

        return StudioPanel::create([
            'tenant_id' => $tenantId,
            'dashboard_id' => $dashboard->id,
            'panel_type' => $data['panel_type'],
            'placement' => PanelPlacement::from($data['placement']),
            'context_collection_id' => $data['context_collection_id'] ?? null,
            'grid_col_span' => $data['grid']['col_span'] ?? 6,
            'grid_row_span' => $data['grid']['row_span'] ?? 4,
            'grid_order' => $data['grid']['order'] ?? 0,
            'header_visible' => $data['header']['visible'] ?? true,
            'header_label' => $data['header']['label'] ?? null,
            'header_icon' => $data['header']['icon'] ?? null,
            'header_color' => $data['header']['color'] ?? null,
            'header_note' => $data['header']['note'] ?? null,
            'config' => $data['config'] ?? [],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }
}
