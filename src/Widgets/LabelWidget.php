<?php

namespace Flexpik\FilamentStudio\Widgets;

class LabelWidget extends AbstractStudioWidget
{
    protected string $view = 'filament-studio::widgets.label-widget';

    protected int|string|array $columnSpan = 'full';
}
