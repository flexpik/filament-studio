<?php

namespace Flexpik\FilamentStudio\Panels\Types;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Flexpik\FilamentStudio\Panels\AbstractStudioPanel;
use Flexpik\FilamentStudio\Widgets\LabelWidget;

class LabelPanel extends AbstractStudioPanel
{
    public static string $key = 'label';

    public static string $label = 'Label';

    public static string $icon = 'heroicon-o-tag';

    public static string $description = 'Display a styled text heading to group panels';

    public static string $widgetClass = LabelWidget::class;

    public static function configSchema(): array
    {
        return [
            Textarea::make('text')
                ->label('Label Text')
                ->required()
                ->rows(2),
            ColorPicker::make('text_color')
                ->label('Text Color'),
            Select::make('text_size')
                ->label('Text Size')
                ->options([
                    'sm' => 'Small',
                    'base' => 'Medium',
                    'lg' => 'Large',
                    'xl' => 'Extra Large',
                    '2xl' => '2XL',
                ])
                ->default('lg'),
        ];
    }

    public static function defaultConfig(): array
    {
        return [
            'text' => '',
            'text_color' => null,
            'text_size' => 'lg',
        ];
    }
}
