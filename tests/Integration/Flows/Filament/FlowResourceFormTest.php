<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Livewire\Livewire;

it('the create page exposes name, slug, description, icon, color, status, logging_mode fields', function () {
    $this->actingAs($this->makeUserWith(['view_flows', 'create_flows']));

    Livewire::test(FlowResource\Pages\CreateFlow::class)
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('slug')
        ->assertFormFieldExists('description')
        ->assertFormFieldExists('icon')
        ->assertFormFieldExists('color')
        ->assertFormFieldExists('status')
        ->assertFormFieldExists('logging_mode');
});

it('saves a new flow', function () {
    $this->actingAs($this->makeUserWith(['view_flows', 'create_flows']));

    Livewire::test(FlowResource\Pages\CreateFlow::class)
        ->fillForm(['name' => 'My Flow', 'slug' => 'my-flow', 'logging_mode' => 'full', 'status' => 'inactive'])
        ->call('create')
        ->assertHasNoFormErrors();

    $flow = StudioFlow::where('slug', 'my-flow')->firstOrFail();
    expect($flow->exists)->toBeTrue();
    expect(StudioFlowVersion::where('flow_id', $flow->id)->where('version', 1)->exists())->toBeTrue();
});

it('blocks users without create_flows', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));

    $this->get(FlowResource::getUrl('create'))->assertForbidden();
});
