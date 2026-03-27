<?php

namespace Flexpik\FilamentStudio\Actions;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Panels\PanelTypeRegistry;

class CreatePanelAction extends Action
{
    protected ?int $dashboardId = null;

    protected PanelPlacement $placement = PanelPlacement::Dashboard;

    protected ?int $contextCollectionId = null;

    public static function getDefaultName(): ?string
    {
        return 'createPanel';
    }

    public function dashboardId(?int $dashboardId): static
    {
        $this->dashboardId = $dashboardId;

        return $this;
    }

    public function placement(PanelPlacement $placement): static
    {
        $this->placement = $placement;

        return $this;
    }

    public function contextCollectionId(?int $collectionId): static
    {
        $this->contextCollectionId = $collectionId;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Add Panel')
            ->icon('heroicon-o-plus')
            ->slideOver()
            ->form(function () {
                $registry = app(PanelTypeRegistry::class);
                $availableTypes = $registry->forPlacement($this->placement);

                $typeOptions = collect($availableTypes)
                    ->mapWithKeys(fn (string $class, string $key) => [$key => $class::$label])
                    ->toArray();

                return [
                    Section::make('Panel Type')->schema([
                        Select::make('panel_type')
                            ->label('Type')
                            ->options($typeOptions)
                            ->required()
                            ->live(),
                    ]),
                    Section::make('Header')->schema([
                        Toggle::make('header_visible')
                            ->label('Show Header')
                            ->default(true),
                        TextInput::make('header_label')
                            ->label('Label'),
                        TextInput::make('header_icon')
                            ->label('Icon'),
                        TextInput::make('header_color')
                            ->label('Color'),
                        TextInput::make('header_note')
                            ->label('Note'),
                    ]),
                    Section::make('Configuration')
                        ->statePath('config')
                        ->schema(function (callable $get) use ($registry) {
                            $panelType = $get('panel_type');
                            if (! $panelType) {
                                return [];
                            }

                            return $registry->configSchema($panelType);
                        }),
                    Section::make('Grid Size')
                        ->schema([
                            TextInput::make('grid_col_span')
                                ->label('Column Span (1-12)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(12)
                                ->default(6),
                            TextInput::make('grid_row_span')
                                ->label('Row Span (1-8)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(8)
                                ->default(4),
                        ])
                        ->visible(fn () => $this->placement->usesGrid()),
                    Section::make('Sort Order')
                        ->schema([
                            TextInput::make('sort_order')
                                ->numeric()
                                ->default(0),
                        ])
                        ->visible(fn () => ! $this->placement->usesGrid()),
                ];
            })
            ->action(function (array $data) {
                $panelData = [
                    'tenant_id' => Filament::getTenant()?->getKey(),
                    'dashboard_id' => $this->dashboardId,
                    'placement' => $this->placement,
                    'context_collection_id' => $this->contextCollectionId,
                    'panel_type' => $data['panel_type'],
                    'header_visible' => $data['header_visible'] ?? true,
                    'header_label' => $data['header_label'] ?? null,
                    'header_icon' => $data['header_icon'] ?? null,
                    'header_color' => $data['header_color'] ?? null,
                    'header_note' => $data['header_note'] ?? null,
                    'grid_col_span' => $data['grid_col_span'] ?? 6,
                    'grid_row_span' => $data['grid_row_span'] ?? 4,
                    'grid_order' => $data['grid_order'] ?? 0,
                    'sort_order' => $data['sort_order'] ?? 0,
                    'config' => $data['config'] ?? [],
                ];

                StudioPanel::create($panelData);

                $this->success();
            });
    }
}
