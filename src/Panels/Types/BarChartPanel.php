<?php

namespace Flexpik\FilamentStudio\Panels\Types;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Facades\Filament;
use Flexpik\FilamentStudio\Enums\AggregateFunction;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Panels\AbstractStudioPanel;
use Flexpik\FilamentStudio\Widgets\BarChartWidget;

class BarChartPanel extends AbstractStudioPanel
{
    public static string $key = 'bar_chart';

    public static string $label = 'Bar Chart';

    public static string $icon = 'heroicon-o-chart-bar-square';

    public static string $description = 'Display grouped aggregate values as a bar chart';

    public static string $widgetClass = BarChartWidget::class;

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
            Toggle::make('horizontal')
                ->label('Horizontal bars')
                ->default(false),
            TextInput::make('decimal_precision')
                ->label('Decimal Places')
                ->numeric()
                ->default(0),
        ];
    }

    public static function defaultConfig(): array
    {
        return [
            'horizontal' => false,
            'decimal_precision' => 0,
        ];
    }
}
