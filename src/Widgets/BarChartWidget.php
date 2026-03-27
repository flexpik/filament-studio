<?php

namespace Flexpik\FilamentStudio\Widgets;

use Filament\Widgets\ChartWidget;
use Flexpik\FilamentStudio\Concerns\InteractsWithPanelConfig;
use Flexpik\FilamentStudio\Enums\AggregateFunction;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

class BarChartWidget extends ChartWidget
{
    use InteractsWithPanelConfig;

    protected int|string|array $columnSpan = 'full';

    public function mount(?StudioPanel $panel = null, array $variables = [], ?string $recordUuid = null): void
    {
        if ($panel !== null) {
            $this->mountInteractsWithPanelConfig($panel, $variables, $recordUuid);
        }

        parent::mount();
    }

    public function getHeading(): ?string
    {
        return $this->getPanelHeading();
    }

    public function getDescription(): ?string
    {
        return $this->getPanelDescription();
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public function getData(): array
    {
        $config = $this->resolvedConfig();
        $collectionId = $config['collection_id'] ?? null;
        $groupField = $config['group_field'] ?? null;
        $valueField = $config['value_field'] ?? null;
        $functionKey = $config['aggregate_function'] ?? 'count';

        if (! $collectionId || ! $groupField || ! $valueField) {
            return ['datasets' => [], 'labels' => []];
        }

        $collection = StudioCollection::find($collectionId);
        if (! $collection) {
            return ['datasets' => [], 'labels' => []];
        }

        $function = AggregateFunction::from($functionKey);

        $rows = EavQueryBuilder::for($collection)
            ->tenant($this->panel->tenant_id)
            ->aggregateByGroup($function, $valueField, $groupField);

        $labels = $rows->keys()->all();
        $values = $rows->values()->all();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $this->panel->header_label ?? '',
                    'data' => $values,
                ],
            ],
        ];
    }
}
