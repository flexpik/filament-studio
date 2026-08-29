<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages\EditFlow;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\RelationManagers\VersionsRelationManager;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;
use Livewire\Livewire;

it('shows Published vN pill when flow has a published version', function () {
    $this->actingAs($this->makeUserWith(['view_flows', 'update_flows']));
    $flow = StudioFlow::factory()->withPublishedVersion()->create();

    Livewire::test(EditFlow::class, ['record' => $flow->id])
        ->assertSeeText('Published v1');
});

it('shows Draft pill when draft_graph is set', function () {
    $this->actingAs($this->makeUserWith(['view_flows', 'update_flows']));
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $flow->update(['draft_graph' => ['nodes' => [], 'edges' => []]]);

    Livewire::test(EditFlow::class, ['record' => $flow->id])
        ->assertSeeText('Draft');
});

it('publish action accepts a change summary and creates v2', function () {
    $this->actingAs($this->makeUserWith(['view_flows', 'update_flows', 'publish_flows']));
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $flow->update(['draft_graph' => ['nodes' => [['id' => 'new']], 'edges' => []]]);

    Livewire::test(EditFlow::class, ['record' => $flow->id])
        ->callAction('publish', ['change_summary' => 'tweak'])
        ->assertHasNoActionErrors();

    expect($flow->fresh()->publishedVersion->version)->toBe(2);
});

it('restore action on the versions relation creates a new version row', function () {
    $this->actingAs($this->makeUserWith(['view_flows', 'update_flows', 'publish_flows']));
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $oldV1 = $flow->publishedVersion;
    $flow->update(['draft_graph' => ['nodes' => [['id' => 'x']], 'edges' => []]]);
    app(PublishFlowVersion::class)->publish($flow); // → v2

    Livewire::test(VersionsRelationManager::class, [
        'ownerRecord' => $flow,
        'pageClass' => EditFlow::class,
    ])
        ->callTableAction('restore', $oldV1)
        ->assertHasNoTableActionErrors();

    expect($flow->fresh()->publishedVersion->version)->toBe(3);
});
