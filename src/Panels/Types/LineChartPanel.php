<?php

namespace Flexpik\FilamentStudio\Panels\Types;

use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Flexpik\FilamentStudio\Enums\AggregateFunction;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Panels\AbstractStudioPanel;
use Flexpik\FilamentStudio\Widgets\LineChartWidget;

class LineChartPanel extends AbstractStudioPanel
{
    public static string $key = 'line_chart';

    public static string $label = 'Line Chart';

    public static string $icon = 'heroicon-o-chart-bar';

    public static string $description = 'Display multiple data series as grouped line chart';

    public static string $widgetClass = LineChartWidget::class;

    public static function configSchema(): array
    {
        return [
            Select::make('collection_id')
                ->label('Collection')
                ->options(fn () => StudioCollection::query()->forTenant(Filament::getTenant()?->getKey())->pluck('label', 'id'))
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('group_field', null)),
            Select::make('group_field')
                ->label('Group Field (X-axis)')
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
            Repeater::make('series')
                ->label('Series')
                ->schema([
                    Select::make('field')
                        ->label('Field')
                        ->options(function (Get $get) {
                            $collectionId = $get('../../collection_id');
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
                        ->default(AggregateFunction::Count->value)
                        ->required(),
                    TextInput::make('label')
                        ->label('Series Label'),
                    ColorPicker::make('color')
                        ->label('Color'),
                ])
                ->defaultItems(1)
                ->collapsible(),
            TextInput::make('decimal_precision')
                ->label('Decimal Places')
                ->numeric()
                ->default(0),
        ];
    }

    public static function defaultConfig(): array
    {
        return [
            'series' => [],
            'decimal_precision' => 0,
        ];
    }
}
