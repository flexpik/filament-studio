<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('adds published_version_id, draft_graph, draft_updated_at to studio_flows', function () {
    $t = config('filament-studio.table_prefix', 'studio_').'flows';
    expect(Schema::hasColumn($t, 'published_version_id'))->toBeTrue()
        ->and(Schema::hasColumn($t, 'draft_graph'))->toBeTrue()
        ->and(Schema::hasColumn($t, 'draft_updated_at'))->toBeTrue();
});

it('adds published_by to studio_flow_versions and the (flow_id, published_at) index', function () {
    $t = config('filament-studio.table_prefix', 'studio_').'flow_versions';
    expect(Schema::hasColumn($t, 'published_by'))->toBeTrue();
    $indexes = collect(Schema::getIndexes($t))->pluck('columns');
    expect($indexes->contains(fn ($cols) => $cols === ['flow_id', 'published_at']))->toBeTrue();
});

it('adds inline_graph to studio_flow_runs and makes flow_version_id nullable', function () {
    $t = config('filament-studio.table_prefix', 'studio_').'flow_runs';
    expect(Schema::hasColumn($t, 'inline_graph'))->toBeTrue();
    $columns = collect(Schema::getColumns($t));
    $col = $columns->firstWhere('name', 'flow_version_id');
    expect($col['nullable'] ?? false)->toBeTrue();
});
