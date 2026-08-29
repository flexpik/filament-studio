<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;

it('the design page returns 200 with canvas mount div and API key meta tag', function () {
    $this->withoutVite();
    $this->actingAs($this->makeUserWith(['view_flows', 'update_flows']));
    $flow = StudioFlow::factory()->create();

    $response = $this->get(FlowResource::getUrl('design', ['record' => $flow]));

    $response->assertOk();
    $response->assertSee('id="flow-canvas-root"', false);
    $response->assertSee('<meta name="studio-api-key"', false);
});

it('the flow canvas bundle exists in the Vite manifest', function () {
    $manifest = '/var/www/html/crud/public/build/manifest.json';

    if (! file_exists($manifest)) {
        $this->markTestSkipped('Vite manifest not found — run npm run build first.');
    }

    $contents = json_decode(file_get_contents($manifest), true);
    $found = false;
    foreach ($contents as $key => $entry) {
        if (str_contains($key, 'flow-canvas')) {
            $found = true;
            break;
        }
    }

    expect($found)->toBeTrue('flow-canvas entry not found in Vite manifest');
});
