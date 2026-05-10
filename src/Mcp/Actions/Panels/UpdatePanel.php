<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Panels;

use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdatePanel
{
    /** @param array<string, mixed> $input */
    public function __invoke(array $input, int $tenantId): StudioPanel
    {
        $data = Validator::validate($input, [
            'id' => ['required', 'integer'],
            'placement' => ['nullable', 'string', Rule::in(array_map(fn ($c) => $c->value, PanelPlacement::cases()))],
            'grid' => ['nullable', 'array'],
            'header' => ['nullable', 'array'],
            'config' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $panel = StudioPanel::query()->where('tenant_id', $tenantId)->find($data['id']);
        if ($panel === null) {
            throw new StudioNotFoundException('panel', (string) $data['id']);
        }

        if (isset($data['placement'])) {
            $panel->placement = PanelPlacement::from($data['placement']);
        }
        if (isset($data['grid']['col_span'])) {
            $panel->grid_col_span = $data['grid']['col_span'];
        }
        if (isset($data['grid']['row_span'])) {
            $panel->grid_row_span = $data['grid']['row_span'];
        }
        if (isset($data['grid']['order'])) {
            $panel->grid_order = $data['grid']['order'];
        }
        if (isset($data['header']['visible'])) {
            $panel->header_visible = (bool) $data['header']['visible'];
        }
        foreach (['label', 'icon', 'color', 'note'] as $h) {
            if (array_key_exists($h, $data['header'] ?? [])) {
                $panel->{'header_'.$h} = $data['header'][$h];
            }
        }
        if (array_key_exists('config', $data)) {
            $panel->config = $data['config'];
        }
        if (array_key_exists('sort_order', $data)) {
            $panel->sort_order = $data['sort_order'];
        }
        $panel->save();

        return $panel;
    }
}
