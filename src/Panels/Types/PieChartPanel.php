<?php

namespace Flexpik\FilamentStudio\Panels\Types;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Flexpik\FilamentStudio\Enums\AggregateFunction;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Panels\AbstractStudioPanel;
use Flexpik\FilamentStudio\Widgets\PieChartWidget;

class PieChartPanel extends AbstractStudioPanel
{
    public static string $key = 'pie_chart';

    public static string $label = 'Pie Chart';

    public static string $icon = 'heroicon-o-chart-pie';

    public static string $description = 'Visualise proportional data across categories';

    public static string $widgetClass = PieChartWidget::class;

    public static function configSchema(): array
    {
        return [
            Select::make('collection_id')
                ->label('Collection')
                ->options(fn () => StudioCollection::query()->forTenant(Filament::getTenant()?->getKey())->pluck('label', 'id'))
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('group_field', null) || $set('value_field', null)),
            Select::make('group_field')
                ->label('Group Field')
                ->options(function (Get $get) {
                    $collectionId = $get('collection_id');
                    if (! $collectionId) {
                        return [];
                    }

                    return StudioField::query()
                        ->where('collection_id', $collectionId)
                        ->pluck('label', 'column_name');
                })
                ->required(),
            Select::make('value_field')
                ->label('Value Field')
                ->options(function (Get $get) {
                    $collectionId = $get('collection_id');
                    if (! $collectionId) {
                        return [];
                    }

                    return StudioField::query()
                        ->where('collection_id', $collectionId)
                        ->pluck('label', 'column_name');
                })
                ->required(),
            Select::make('aggregate_function')
                ->label('Function')
                ->options(
                    collect(AggregateFunction::cases())
                        ->mapWithKeys(fn (AggregateFunction $fn) => [$fn->value => $fn->label()])
                        ->toArray()
                )
                ->required(),
            Toggle::make('donut')
                ->label('Donut style')
                ->default(false),
            Toggle::make('show_labels')
                ->label('Show labels')
                ->default(true),
            Toggle::make('show_legend')
                ->label('Show legend')
                ->default(true),
        ];
    }

    public static function defaultConfig(): array
    {
        return [
            'donut' => false,
            'show_labels' => true,
            'show_legend' => true,
        ];
    }
}
