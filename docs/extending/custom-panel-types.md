# Custom Panel Types

Extend the dashboard system with custom panel types for specialized visualizations or interactive components.

## Creating a Panel Type

A custom panel type requires two classes: the panel definition and its Livewire widget.

### Panel Definition

Extend `AbstractStudioPanel`:

```php
<?php

namespace App\Studio\Panels;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Panels\AbstractStudioPanel;

class MapPanel extends AbstractStudioPanel
{
    protected static string $key = 'map';
    protected static string $label = 'Map';
    protected static string $icon = 'heroicon-o-map';
    protected static string $description = 'Display records on an interactive map';
    protected static string $widgetClass = MapWidget::class;
    protected static array $supportedPlacements = [
        PanelPlacement::Dashboard,
        PanelPlacement::CollectionHeader,
    ];

    public function configSchema(): array
    {
        return [
            Select::make('collection_id')
                ->label('Collection')
                ->options(fn () => StudioCollection::pluck('label', 'id'))
                ->required(),
            TextInput::make('lat_field')
                ->label('Latitude Field')
                ->required(),
            TextInput::make('lng_field')
                ->label('Longitude Field')
                ->required(),
            TextInput::make('zoom')
                ->label('Default Zoom Level')
                ->numeric()
                ->default(10),
        ];
    }

    public function defaultConfig(): array
    {
        return [
            'zoom' => 10,
        ];
    }
}
```

### Widget Class

Create a Livewire widget that renders the panel:

```php
<?php

namespace App\Studio\Widgets;

use Flexpik\FilamentStudio\Widgets\AbstractStudioWidget;

class MapWidget extends AbstractStudioWidget
{
    protected static string $view = 'studio.widgets.map';

    public function getData(): array
    {
        $collectionId = $this->config('collection_id');
        $collection = StudioCollection::find($collectionId);

        if (! $collection) {
            return ['markers' => []];
        }

        $records = EavQueryBuilder::for($collection)
            ->tenant($this->tenantId())
            ->get();

        $latField = $this->config('lat_field');
        $lngField = $this->config('lng_field');

        return [
            'markers' => $records->map(fn ($r) => [
                'lat' => $r->{$latField},
                'lng' => $r->{$lngField},
            ])->toArray(),
            'zoom' => $this->config('zoom', 10),
        ];
    }
}
```

## Required Properties

| Property | Type | Description |
|----------|------|-------------|
| `$key` | `string` | Unique identifier for the panel type. |
| `$label` | `string` | Display name in the panel type picker. |
| `$icon` | `string` | Heroicon for the picker. |
| `$description` | `string` | Short description shown in the picker. |
| `$widgetClass` | `string` | Fully qualified class name of the Livewire widget. |
| `$supportedPlacements` | `array` | Array of `PanelPlacement` enum values where this panel can be placed. |

## Required Methods

### `configSchema(): array`

Returns a Filament form schema for panel configuration. The config is stored as JSON in the `StudioPanel.config` column.

### `defaultConfig(): array`

Returns default config values. These are merged with the stored config when accessed via `$panel->merged_config`.

## Panel Placements

Choose which contexts your panel supports:

| Placement | Description |
|-----------|-------------|
| `PanelPlacement::Dashboard` | Main dashboard grid (12-column layout) |
| `PanelPlacement::CollectionHeader` | Above the record list table |
| `PanelPlacement::CollectionFooter` | Below the record list table |
| `PanelPlacement::RecordHeader` | Above the record detail/edit form |
| `PanelPlacement::RecordFooter` | Below the record detail/edit form |

Dashboard panels use grid positioning. Non-dashboard panels use simple sort ordering.

## Accessing Configuration in Widgets

The `InteractsWithPanelConfig` trait (used by `AbstractStudioWidget`) provides:

```php
// Get a config value
$this->config('collection_id');
$this->config('zoom', 10); // with default

// Get all config with variables resolved
$this->resolvedConfig();

// Get current tenant ID
$this->tenantId();

// Access the panel model
$this->panel;
$this->panel->header_label;
```

## Registering Custom Panel Types

```php
use App\Studio\Panels\MapPanel;

FilamentStudioPlugin::make()
    ->panelTypes([
        MapPanel::class,
    ]);
```

The panel type is automatically registered by its `$key` property.
