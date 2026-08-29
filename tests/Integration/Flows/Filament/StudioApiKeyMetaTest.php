<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Models\StudioApiKey;

it('the design page emits a studio-api-key meta tag', function () {
    $this->withoutVite();
    $this->actingAs($this->makeUserWith(['view_flows', 'update_flows']));
    $flow = StudioFlow::factory()->create();

    $response = $this->get(FlowResource::getUrl('design', ['record' => $flow]));

    $response->assertOk();
    $response->assertSee('<meta name="studio-api-key"', false);

    $key = StudioApiKey::first();
    expect($key)->not->toBeNull();
    expect($key->permissions['_studio'])->toContain('flows');
    expect($key->expires_at)->not->toBeNull();
    expect($key->is_active)->toBeTrue();
});
