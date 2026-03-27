<?php

namespace Flexpik\FilamentStudio\Resources\DashboardResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Flexpik\FilamentStudio\Resources\DashboardResource;

class CreateDashboard extends CreateRecord
{
    protected static string $resource = DashboardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = Filament::getTenant()?->getKey();
        $data['created_by'] = auth()->id();

        return $data;
    }
}
