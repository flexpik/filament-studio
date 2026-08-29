<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;

class CreateFlow extends CreateRecord
{
    protected static string $resource = FlowResource::class;

    protected function afterCreate(): void
    {
        StudioFlowVersion::create([
            'flow_id' => $this->record->id,
            'version' => 1,
            'graph' => ['nodes' => [], 'edges' => []],
        ]);
    }
}
