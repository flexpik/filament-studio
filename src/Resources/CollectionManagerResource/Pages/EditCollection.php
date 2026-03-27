<?php

namespace Flexpik\FilamentStudio\Resources\CollectionManagerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioMigrationLog;
use Flexpik\FilamentStudio\Resources\CollectionManagerResource;

class EditCollection extends EditRecord
{
    protected static string $resource = CollectionManagerResource::class;

    protected ?array $beforeState = null;

    protected function beforeSave(): void
    {
        $this->beforeState = $this->record->fresh()->toArray();
    }

    protected function afterSave(): void
    {
        /** @var StudioCollection $record */
        $record = $this->record;

        StudioMigrationLog::create([
            'tenant_id' => $record->tenant_id,
            'collection_id' => $record->id,
            'operation' => 'update_collection',
            'before_state' => $this->beforeState,
            'after_state' => $record->fresh()->toArray(),
            'performed_by' => auth()->id(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('auditLog')
                ->label('Audit Log')
                ->icon('heroicon-o-document-magnifying-glass')
                ->slideOver()
                ->modalContent(function () {
                    /** @var StudioCollection $record */
                    $record = $this->record;

                    $logs = StudioMigrationLog::where('collection_id', $record->id)
                        ->orderByDesc('created_at')
                        ->limit(50)
                        ->get();

                    return view('studio.audit-log', ['logs' => $logs]);
                }),
        ];
    }
}
