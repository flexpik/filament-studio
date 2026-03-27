<?php

use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Panels\AbstractStudioPanel;
use Flexpik\FilamentStudio\Panels\PanelTypeRegistry;

beforeEach(function () {
    $this->registry = new PanelTypeRegistry;
});

it('registers and retrieves a panel type', function () {
    $this->registry->register(FakePanelType::class);

    expect($this->registry->all())->toHaveKey('fake_panel');
    expect($this->registry->get('fake_panel'))->toBe(FakePanelType::class);
});

it('throws on registering non-AbstractStudioPanel class', function () {
    $this->registry->register(stdClass::class);
})->throws(InvalidArgumentException::class);

it('throws on retrieving unregistered panel type', function () {
    $this->registry->get('nonexistent');
})->throws(InvalidArgumentException::class);

it('returns config schema for a panel type', function () {
    $this->registry->register(FakePanelType::class);

    $schema = $this->registry->configSchema('fake_panel');

    expect($schema)->toBeArray();
});

it('filters panel types by supported placement', function () {
    $this->registry->register(FakePanelType::class);
    $this->registry->register(DashboardOnlyPanelType::class);

    $collectionTypes = $this->registry->forPlacement(PanelPlacement::CollectionHeader);

    expect($collectionTypes)->toHaveKey('fake_panel')
        ->not->toHaveKey('dashboard_only');
});

// --- Test doubles ---

class FakePanelType extends AbstractStudioPanel
{
    public static string $key = 'fake_panel';

    public static string $label = 'Fake Panel';

    public static string $icon = 'heroicon-o-star';

    public static string $widgetClass = 'FakeWidget';

    public static array $supportedPlacements = [
        PanelPlacement::Dashboard,
        PanelPlacement::CollectionHeader,
        PanelPlacement::CollectionFooter,
    ];

    public static function configSchema(): array
    {
        return [];
    }
}

class DashboardOnlyPanelType extends AbstractStudioPanel
{
    public static string $key = 'dashboard_only';

    public static string $label = 'Dashboard Only';

    public static string $icon = 'heroicon-o-chart-bar';

    public static string $widgetClass = 'FakeWidget';

    public static array $supportedPlacements = [PanelPlacement::Dashboard];

    public static function configSchema(): array
    {
        return [];
    }
}
