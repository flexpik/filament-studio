<?php

namespace Flexpik\FilamentStudio\Widgets;

use Filament\Widgets\ChartWidget;
use Flexpik\FilamentStudio\Concerns\InteractsWithPanelConfig;
use Flexpik\FilamentStudio\Enums\AggregateFunction;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

class LineChartWidget extends ChartWidget
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
        return 'line';
    }

    public function getData(): array
    {
        $config = $this->resolvedConfig();
        $collectionId = $config['collection_id'] ?? null;
        $groupField = $config['group_field'] ?? null;
        $seriesConfig = $config['series'] ?? [];

        if (! $collectionId || ! $groupField || empty($seriesConfig)) {
            return ['datasets' => [], 'labels' => []];
        }

        $collection = StudioCollection::find($collectionId);
        if (! $collection) {
            return ['datasets' => [], 'labels' => []];
        }

        $allLabels = collect();
        $datasets = [];

        foreach ($seriesConfig as $series) {
            $fieldName = $series['field'] ?? null;
            $functionKey = $series['aggregate_function'] ?? AggregateFunction::Count->value;

            if (! $fieldName) {
                continue;
            }

            $function = AggregateFunction::from($functionKey);

            $rows = EavQueryBuilder::for($collection)
                ->tenant($this->panel->tenant_id)
                ->aggregateByGroup($function, $fieldName, $groupField);

            $allLabels = $allLabels->merge($rows->keys())->unique()->sort()->values();

            $dataset = [
                'label' => $series['label'] ?? $fieldName,
                'data' => $rows->values()->all(),
            ];

            if (! empty($series['color'])) {
                $dataset['borderColor'] = $series['color'];
                $dataset['backgroundColor'] = $series['color'];
            }

            $datasets[] = $dataset;
        }

        return [
            'labels' => $allLabels->all(),
            'datasets' => $datasets,
        ];
    }
}
