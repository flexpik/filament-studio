<?php

namespace Flexpik\FilamentStudio\Panels\Types;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Panels\AbstractStudioPanel;
use Flexpik\FilamentStudio\Widgets\VariableWidget;

class VariablePanel extends AbstractStudioPanel
{
    public static string $key = 'variable';

    public static string $label = 'Variable';

    public static string $icon = 'heroicon-o-adjustments-horizontal';

    public static string $description = 'Interactive input that controls other panels via variables';

    public static string $widgetClass = VariableWidget::class;

    /** @var list<PanelPlacement> */
    public static array $supportedPlacements = [PanelPlacement::Dashboard];

    public static function configSchema(): array
    {
        return [
            TextInput::make('variable_key')
                ->label('Variable Key')
                ->helperText('Other panels reference this as {{key}}')
                ->required()
                ->alphaDash(),
            Select::make('interface')
                ->label('Input Type')
                ->options([
                    'text' => 'Text',
                    'number' => 'Number',
                    'date' => 'Date',
                    'date_range' => 'Date Range',
                    'select' => 'Select',
                ])
                ->required()
                ->default('text'),
            TextInput::make('default_value')
                ->label('Default Value'),
            TextInput::make('label')
                ->label('Label'),
        ];
    }
}
