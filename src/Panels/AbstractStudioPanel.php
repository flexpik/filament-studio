<?php

namespace Flexpik\FilamentStudio\Panels;

use Filament\Schemas\Components\Component;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Models\StudioPanel;

abstract class AbstractStudioPanel
{
    /** Unique key for this panel type (e.g., 'metric', 'time_series'). */
    public static string $key;

    /** Human-readable label for the panel type picker. */
    public static string $label;

    /** Heroicon name for the panel type picker. */
    public static string $icon;

    /** One-line description shown in the panel type picker. */
    public static string $description = '';

    /** The widget class that renders this panel type. */
    public static string $widgetClass;

    /**
     * Which placement contexts this panel type supports.
     *
     * @var list<PanelPlacement>
     */
    public static array $supportedPlacements = [
        PanelPlacement::Dashboard,
        PanelPlacement::CollectionHeader,
        PanelPlacement::CollectionFooter,
        PanelPlacement::RecordHeader,
        PanelPlacement::RecordFooter,
    ];

    /**
     * Return the Filament form schema for configuring this panel type.
     *
     * @return array<Component>
     */
    abstract public static function configSchema(): array;

    /**
     * Return default config values for a new panel of this type.
     *
     * @return array<string, mixed>
     */
    public static function defaultConfig(): array
    {
        return [];
    }

    /**
     * Check whether this panel type supports a given placement.
     */
    public static function supportsPlacement(PanelPlacement $placement): bool
    {
        return in_array($placement, static::$supportedPlacements);
    }

    /**
     * Instantiate the widget class for a given panel record.
     */
    public static function makeWidget(StudioPanel $panel): object
    {
        $widgetClass = static::$widgetClass;

        return new $widgetClass($panel);
    }
}
