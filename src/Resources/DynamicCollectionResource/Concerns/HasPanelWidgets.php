<?php

namespace Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Concerns;

use Filament\Facades\Filament;
use Filament\Widgets\WidgetConfiguration;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Panels\PanelTypeRegistry;

trait HasPanelWidgets
{
    /**
     * @return array<WidgetConfiguration>
     */
    protected function buildWidgetsForPlacement(PanelPlacement $placement, ?string $recordUuid = null): array
    {
        $collection = $this->getResolvedCollection();
        $tenantId = Filament::getTenant()?->getKey();
        $registry = app(PanelTypeRegistry::class);

        $panels = StudioPanel::query()
            ->forPlacement($placement, $collection->id)
            ->where('tenant_id', $tenantId)
            ->get();

        $widgets = [];

        foreach ($panels as $panel) {
            if (! isset($registry->all()[$panel->panel_type])) {
                continue;
            }

            $panelType = $registry->get($panel->panel_type);
            $widgetClass = $panelType::$widgetClass;

            $params = ['panel' => $panel];
            if ($recordUuid !== null) {
                $params['recordUuid'] = $recordUuid;
            }

            $widgets[] = $widgetClass::make($params);
        }

        return $widgets;
    }
}
