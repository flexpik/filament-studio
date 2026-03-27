<?php

namespace Flexpik\FilamentStudio\Resources\DashboardResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Flexpik\FilamentStudio\Resources\DashboardResource;

class ListDashboards extends ListRecords
{
    protected static string $resource = DashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
