<?php

namespace Flexpik\FilamentStudio\Resources\DashboardResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Flexpik\FilamentStudio\Actions\CreatePanelAction;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Resources\DashboardResource;

class EditDashboard extends EditRecord
{
    protected static string $resource = DashboardResource::class;

    protected function getHeaderActions(): array
    {
        /** @var StudioDashboard $record */
        $record = $this->record;

        return [
            CreatePanelAction::make()
                ->dashboardId($record->id)
                ->placement(PanelPlacement::Dashboard),
            DeleteAction::make(),
        ];
    }
}
