<?php

namespace Flexpik\FilamentStudio\Panels\Types;

use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Flexpik\FilamentStudio\Enums\AggregateFunction;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Panels\AbstractStudioPanel;
use Flexpik\FilamentStudio\Widgets\MetricWidget;

class MetricPanel extends AbstractStudioPanel
{
    public static string $key = 'metric';

    public static string $label = 'Metric';

    public static string $icon = 'heroicon-o-hashtag';

    public static string $description = 'Display a single aggregate value as a large number';

    public static string $widgetClass = MetricWidget::class;

    public static function configSchema(): array
    {
        return [
            Select::make('collection_id')
                ->label('Collection')
                ->options(fn () => StudioCollection::query()->forTenant(Filament::getTenant()?->getKey())->pluck('label', 'id'))
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('field', null)),
            Select::make('field')
                ->label('Field')
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
            TextInput::make('prefix')
                ->label('Prefix'),
            TextInput::make('suffix')
                ->label('Suffix'),
            TextInput::make('decimal_precision')
                ->label('Decimal Places')
                ->numeric()
                ->default(0),
            Toggle::make('abbreviate')
                ->label('Abbreviate large numbers')
                ->helperText('2000 → 2K')
                ->default(false),
            Repeater::make('conditional_styles')
                ->label('Conditional Styles')
                ->schema([
                    Select::make('operator')
                        ->options(['>' => '>', '>=' => '>=', '<' => '<', '<=' => '<=', '=' => '='])
                        ->required(),
                    TextInput::make('threshold')
                        ->numeric()
                        ->required(),
                    ColorPicker::make('color')
                        ->required(),
                ])
                ->defaultItems(0)
                ->collapsible(),
        ];
    }

    public static function defaultConfig(): array
    {
        return [
            'abbreviate' => false,
            'decimal_precision' => 0,
            'conditional_styles' => [],
        ];
    }
}
