<?php

namespace Flexpik\FilamentStudio\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flexpik\FilamentStudio\Concerns\InteractsWithPanelConfig;
use Flexpik\FilamentStudio\Enums\AggregateFunction;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

class MetricWidget extends StatsOverviewWidget
{
    use InteractsWithPanelConfig;

    protected int|string|array $columnSpan = 'full';

    public function mount(StudioPanel $panel, array $variables = [], ?string $recordUuid = null): void
    {
        $this->mountInteractsWithPanelConfig($panel, $variables, $recordUuid);
    }

    /**
     * @return array<Stat>
     */
    public function getStats(): array
    {
        $config = $this->resolvedConfig();
        $collectionId = $config['collection_id'] ?? null;
        $fieldName = $config['field'] ?? null;
        $functionKey = $config['aggregate_function'] ?? 'count';

        if (! $collectionId || ! $fieldName) {
            return [Stat::make($this->panel->header_label ?? 'Metric', '—')];
        }

        $collection = StudioCollection::find($collectionId);
        if (! $collection) {
            return [Stat::make($this->panel->header_label ?? 'Metric', '—')];
        }

        $function = AggregateFunction::from($functionKey);

        $query = EavQueryBuilder::for($collection)
            ->tenant($this->panel->tenant_id);

        $rawValue = $query->aggregate($function, $fieldName);

        $value = $this->formatValue($rawValue, $config);
        $label = $this->panel->header_label ?? 'Metric';

        $stat = Stat::make($label, $value);

        if ($this->panel->header_note) {
            $stat->description($this->panel->header_note);
        }

        if ($this->panel->header_icon) {
            $stat->icon($this->panel->header_icon);
        }

        $color = $this->resolveConditionalColor($rawValue, $config['conditional_styles'] ?? []);
        if ($color) {
            $stat->color($color);
        }

        return [$stat];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function formatValue(mixed $rawValue, array $config): string
    {
        if ($rawValue === null) {
            return '—';
        }

        $precision = (int) ($config['decimal_precision'] ?? 0);
        $value = round((float) $rawValue, $precision);

        if (! empty($config['abbreviate']) && abs($value) >= 1000) {
            $value = $this->abbreviateNumber($value);
        } else {
            $value = number_format($value, $precision);
        }

        $prefix = $config['prefix'] ?? '';
        $suffix = $config['suffix'] ?? '';

        return $prefix.$value.$suffix;
    }

    protected function abbreviateNumber(float $value): string
    {
        $suffixes = ['', 'K', 'M', 'B', 'T'];
        $index = 0;

        while (abs($value) >= 1000 && $index < count($suffixes) - 1) {
            $value /= 1000;
            $index++;
        }

        return round($value, 1).$suffixes[$index];
    }

    /**
     * @param  array<array{operator: string, threshold: numeric, color: string}>  $styles
     */
    protected function resolveConditionalColor(mixed $rawValue, array $styles): ?string
    {
        if ($rawValue === null || empty($styles)) {
            return null;
        }

        $numericValue = (float) $rawValue;

        foreach ($styles as $style) {
            $threshold = (float) $style['threshold'];
            $matches = match ($style['operator']) {
                '>' => $numericValue > $threshold,
                '>=' => $numericValue >= $threshold,
                '<' => $numericValue < $threshold,
                '<=' => $numericValue <= $threshold,
                '=' => $numericValue === $threshold,
                default => false,
            };

            if ($matches) {
                return $style['color'];
            }
        }

        return null;
    }
}
