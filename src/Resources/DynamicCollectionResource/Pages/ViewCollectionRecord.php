<?php

namespace Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Pages;

use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ViewRecord;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioRecordVersion;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Concerns\HasPanelWidgets;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Concerns\ResolvesCollection;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Database\Eloquent\Model;

class ViewCollectionRecord extends ViewRecord
{
    use HasPanelWidgets;
    use ResolvesCollection;

    protected static string $resource = DynamicCollectionResource::class;

    public function mount(int|string $record): void
    {
        $this->initializeCollectionSlug();
        DynamicCollectionResource::$currentPageContext = 'edit';

        parent::mount($record);
    }

    public function getTitle(): string
    {
        return $this->getResolvedCollection()->label;
    }

    /**
     * Resolve the record by UUID.
     */
    protected function resolveRecord(int|string $key): Model
    {
        $collection = $this->getResolvedCollection();

        return StudioRecord::query()
            ->where(fn ($q) => $q->where('uuid', $key)->orWhere('id', $key))
            ->where('collection_id', $collection->id)
            ->forTenant(Filament::getTenant()?->getKey())
            ->firstOrFail();
    }

    /**
     * Fill the form with EAV values for display.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $collection = $this->getResolvedCollection();

        /** @var StudioRecord $record */
        $record = $this->getRecord();

        $eavData = EavQueryBuilder::for($collection)
            ->tenant(Filament::getTenant()?->getKey())
            ->getRecordData($record);

        return array_merge($data, $eavData);
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\EditAction::make(),
        ];

        $collection = $this->getResolvedCollection();

        if ($collection->enable_versioning) {
            $actions[] = Actions\Action::make('versionHistory')
                ->label('Version History')
                ->icon('heroicon-o-clock')
                ->slideOver()
                ->modalContent(function () {
                    /** @var StudioRecord $record */
                    $record = $this->getRecord();
                    $collection = $this->getResolvedCollection();

                    $versions = StudioRecordVersion::with('creator')
                        ->where('record_id', $record->id)
                        ->orderByDesc('created_at')
                        ->get();

                    $fields = $collection->fields()->get();

                    $fieldLabels = $fields->pluck('label', 'column_name')->all();
                    $fieldTypes = $fields->pluck('eav_cast', 'column_name')
                        ->map(fn ($cast) => $cast instanceof \Flexpik\FilamentStudio\Enums\EavCast ? $cast->value : (string) $cast)
                        ->all();

                    return view('filament-studio::version-history', [
                        'versions' => $versions,
                        'fieldLabels' => $fieldLabels,
                        'fieldTypes' => $fieldTypes,
                        'showRestore' => false,
                    ]);
                });
        }

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        /** @var StudioRecord|null $record */
        $record = $this->record instanceof Model ? $this->record : null;

        return $this->buildWidgetsForPlacement(PanelPlacement::RecordHeader, $record?->uuid);
    }

    protected function getFooterWidgets(): array
    {
        /** @var StudioRecord|null $record */
        $record = $this->record instanceof Model ? $this->record : null;

        return $this->buildWidgetsForPlacement(PanelPlacement::RecordFooter, $record?->uuid);
    }
}
