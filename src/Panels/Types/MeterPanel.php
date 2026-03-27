<?php

namespace Flexpik\FilamentStudio\Panels\Types;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Facades\Filament;
use Flexpik\FilamentStudio\Enums\AggregateFunction;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Panels\AbstractStudioPanel;
use Flexpik\FilamentStudio\Widgets\MeterWidget;

class MeterPanel extends AbstractStudioPanel
{
    public static string $key = 'meter';

    public static string $label = 'Meter';

    public static string $icon = 'heroicon-o-signal';

    public static string $description = 'Display an aggregate value as a radial gauge';

    public static string $widgetClass = MeterWidget::class;

    /** @var list<PanelPlacement> */
    public static array $supportedPlacements = [
        PanelPlacement::Dashboard,
        PanelPlacement::CollectionHeader,
        PanelPlacement::RecordHeader,
    ];

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
            TextInput::make('maximum')
                ->label('Maximum value')
                ->numeric()
                ->required()
                ->default(100),
            Select::make('size')
                ->label('Size')
                ->options([
                    'full' => 'Full circle',
                    'half' => 'Half circle',
                ])
                ->default('full'),
            TextInput::make('stroke_width')
                ->label('Stroke width')
                ->numeric()
                ->default(10),
            ColorPicker::make('color')
                ->label('Color')
                ->default('#3b82f6'),
            Toggle::make('rounded_stroke')
                ->label('Rounded stroke')
                ->default(true),
            TextInput::make('decimal_precision')
                ->label('Decimal Places')
                ->numeric()
                ->default(0),
        ];
    }

    public static function defaultConfig(): array
    {
        return [
            'maximum' => 100,
            'size' => 'full',
            'stroke_width' => 10,
            'color' => '#3b82f6',
            'rounded_stroke' => true,
            'decimal_precision' => 0,
        ];
    }
}
