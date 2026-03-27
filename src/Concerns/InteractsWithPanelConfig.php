<?php

namespace Flexpik\FilamentStudio\Concerns;

use Filament\Facades\Filament;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Services\VariableResolver;
use Livewire\Attributes\On;

trait InteractsWithPanelConfig
{
    public StudioPanel $panel;

    /** @var array<string, mixed> */
    public array $variables = [];

    public ?string $recordUuid = null;

    public function mountInteractsWithPanelConfig(StudioPanel $panel, array $variables = [], ?string $recordUuid = null): void
    {
        $this->panel = $panel;
        $this->variables = $variables;
        $this->recordUuid = $recordUuid;

        if ($panel->placement->isDashboard()) {
            $this->columnSpan = $panel->grid_col_span;
        }
    }

    protected function config(string $key, mixed $default = null): mixed
    {
        return $this->panel->configValue($key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolvedConfig(): array
    {
        $resolver = app(VariableResolver::class);

        return $resolver->resolveTree(
            $this->panel->config ?? [],
            $this->variables,
            $this->recordUuid,
        );
    }

    protected function tenantId(): ?int
    {
        return Filament::getTenant()?->getKey() ?? $this->panel->tenant_id;
    }

    #[On('studioVariableChanged')]
    public function handleVariableChanged(string $key, mixed $value): void
    {
        $this->variables[$key] = $value;
    }

    public function getPanelHeading(): ?string
    {
        if (! $this->panel->header_visible) {
            return null;
        }

        return $this->panel->header_label;
    }

    public function getPanelDescription(): ?string
    {
        return $this->panel->header_note;
    }
}
