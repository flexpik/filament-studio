<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource;

class ListFlows extends ListRecords
{
    protected static string $resource = FlowResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
