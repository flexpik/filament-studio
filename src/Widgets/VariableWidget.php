<?php

namespace Flexpik\FilamentStudio\Widgets;

use Flexpik\FilamentStudio\Models\StudioPanel;

class VariableWidget extends AbstractStudioWidget
{
    protected string $view = 'filament-studio::widgets.variable-widget';

    public mixed $inputValue = null;

    public function mount(StudioPanel $panel, array $variables = [], ?string $recordUuid = null): void
    {
        $this->mountInteractsWithPanelConfig($panel, $variables, $recordUuid);

        $this->inputValue = $this->config('default_value');
    }

    public function updatedInputValue(): void
    {
        $key = $this->config('variable_key', 'var');

        $this->dispatch('studioVariableChanged', key: $key, value: $this->inputValue);
    }
}
