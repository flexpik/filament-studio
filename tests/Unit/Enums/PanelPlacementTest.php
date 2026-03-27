<?php

use Flexpik\FilamentStudio\Enums\PanelPlacement;

it('has all five placement contexts', function () {
    expect(PanelPlacement::cases())->toHaveCount(5);
});

it('identifies dashboard placement', function () {
    expect(PanelPlacement::Dashboard->isDashboard())->toBeTrue();
    expect(PanelPlacement::CollectionHeader->isDashboard())->toBeFalse();
});

it('identifies collection placements', function () {
    expect(PanelPlacement::CollectionHeader->isCollectionPlacement())->toBeTrue();
    expect(PanelPlacement::CollectionFooter->isCollectionPlacement())->toBeTrue();
    expect(PanelPlacement::Dashboard->isCollectionPlacement())->toBeFalse();
});

it('identifies record placements', function () {
    expect(PanelPlacement::RecordHeader->isRecordPlacement())->toBeTrue();
    expect(PanelPlacement::RecordFooter->isRecordPlacement())->toBeTrue();
    expect(PanelPlacement::CollectionHeader->isRecordPlacement())->toBeFalse();
});

it('identifies header vs footer positions', function () {
    expect(PanelPlacement::CollectionHeader->isHeader())->toBeTrue();
    expect(PanelPlacement::RecordHeader->isHeader())->toBeTrue();
    expect(PanelPlacement::CollectionFooter->isHeader())->toBeFalse();
});

it('requires collection context for collection and record placements', function () {
    expect(PanelPlacement::Dashboard->requiresCollectionContext())->toBeFalse();
    expect(PanelPlacement::CollectionHeader->requiresCollectionContext())->toBeTrue();
    expect(PanelPlacement::RecordHeader->requiresCollectionContext())->toBeTrue();
});

it('returns a human-readable label', function () {
    expect(PanelPlacement::Dashboard->label())->toBe('Dashboard');
    expect(PanelPlacement::CollectionHeader->label())->toBe('Collection Header');
});
