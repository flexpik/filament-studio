<?php

namespace Flexpik\FilamentStudio\Panels\Types;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Flexpik\FilamentStudio\Enums\AggregateFunction;
use Flexpik\FilamentStudio\Enums\CurveType;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Enums\FillType;
use Flexpik\FilamentStudio\Enums\GroupPrecision;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Panels\AbstractStudioPanel;
use Flexpik\FilamentStudio\Widgets\TimeSeriesWidget;

class TimeSeriesPanel extends AbstractStudioPanel
{
    public static string $key = 'time_series';

    public static string $label = 'Time Series';

    public static string $icon = 'heroicon-o-chart-bar';

    public static string $description = 'Plot aggregate values over time as a line chart';

    public static string $widgetClass = TimeSeriesWidget::class;

    public static function configSchema(): array
    {
        return [
            Select::make('collection_id')
                ->label('Collection')
                ->options(fn () => StudioCollection::query()->forTenant(Filament::getTenant()?->getKey())->pluck('label', 'id'))
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('date_field', null) || $set('value_field', null)),
            Select::make('date_field')
                ->label('Date Field')
                ->options(function (Get $get) {
                    $collectionId = $get('collection_id');
                    if (! $collectionId) {
                        return [];
                    }

                    return StudioField::query()
                        ->where('collection_id', $collectionId)
                        ->where('eav_cast', EavCast::Datetime)
                        ->pluck('label', 'column_name');
                })
                ->required()
                ->live(),
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
            Select::make('group_precision')
                ->label('Group By')
                ->options(
                    collect(GroupPrecision::cases())
                        ->mapWithKeys(fn (GroupPrecision $p) => [$p->value => $p->label()])
                        ->toArray()
                )
                ->default(GroupPrecision::Day->value)
                ->required(),
            Select::make('date_range')
                ->label('Date Range')
                ->options([
                    '7d' => 'Last 7 days',
                    '30d' => 'Last 30 days',
                    '90d' => 'Last 90 days',
                    '1y' => 'Last year',
                    'all' => 'All time',
                ])
                ->default('30d'),
            Select::make('curve_type')
                ->label('Curve')
                ->options(
                    collect(CurveType::cases())
                        ->mapWithKeys(fn (CurveType $c) => [$c->value => $c->label()])
                        ->toArray()
                )
                ->default(CurveType::Smooth->value),
            Select::make('fill_type')
                ->label('Fill')
                ->options(
                    collect(FillType::cases())
                        ->mapWithKeys(fn (FillType $f) => [$f->value => $f->label()])
                        ->toArray()
                )
                ->default(FillType::None->value),
            TextInput::make('decimal_precision')
                ->label('Decimal Places')
                ->numeric()
                ->default(0),
            Toggle::make('show_axes')
                ->label('Show Axes')
                ->default(true),
        ];
    }

    public static function defaultConfig(): array
    {
        return [
            'group_precision' => GroupPrecision::Day->value,
            'date_range' => '30d',
            'curve_type' => CurveType::Smooth->value,
            'fill_type' => FillType::None->value,
            'decimal_precision' => 0,
            'show_axes' => true,
        ];
    }
}
