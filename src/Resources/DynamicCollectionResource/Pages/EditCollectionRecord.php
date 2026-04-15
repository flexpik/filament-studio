<?php

namespace Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Pages;

use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioRecordVersion;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Concerns\HasPanelWidgets;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Concerns\ResolvesCollection;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Flexpik\FilamentStudio\Services\LocaleResolver;
use Illuminate\Database\Eloquent\Model;

class EditCollectionRecord extends EditRecord
{
    use HasPanelWidgets;
    use ResolvesCollection;

    protected static string $resource = DynamicCollectionResource::class;

    /** @var array<string> */
    public array $fallbackFields = [];

    public function mount(int|string $record): void
    {
        $this->initializeCollectionSlug();
        DynamicCollectionResource::$currentPageContext = 'edit';

        parent::mount($record);
    }

    public function getTitle(): string
    {
        return 'Edit '.$this->getResolvedCollection()->label;
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
     * Fill the form with EAV values for this record.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $collection = $this->getResolvedCollection();

        /** @var StudioRecord $record */
        $record = $this->getRecord();

        $locale = app(LocaleResolver::class)->resolve($collection);

        $result = EavQueryBuilder::for($collection)
            ->tenant(Filament::getTenant()?->getKey())
            ->locale($locale)
            ->getRecordDataWithMeta($record);

        $data = array_merge($data, $result['data']);

        $this->fallbackFields = $result['fallbacks'];

        return $data;
    }

    /**
     * Override the default save behavior to use EavQueryBuilder.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $collection = $this->getResolvedCollection();
        $locale = app(LocaleResolver::class)->resolve($collection);

        /** @var StudioRecord $studioRecord */
        $studioRecord = $record;

        EavQueryBuilder::for($collection)
            ->tenant(Filament::getTenant()?->getKey())
            ->locale($locale)
            ->update($studioRecord->id, $data, auth()->id());

        return $record;
    }

    protected function getHeaderActions(): array
    {
        $collection = $this->getResolvedCollection();
        $resolver = app(LocaleResolver::class);

        $actions = [];

        // Locale switcher actions
        if ($resolver->isEnabled()
            && ! empty($collection->supported_locales)
            && $collection->fields()->where('is_translatable', true)->exists()
        ) {
            $activeLocale = $resolver->resolve($collection);

            foreach ($resolver->availableLocales($collection) as $locale) {
                $actions[] = Actions\Action::make("locale_{$locale}")
                    ->label(strtoupper($locale))
                    ->size('sm')
                    ->color($activeLocale === $locale ? 'primary' : 'gray')
                    ->action(function () use ($locale) {
                        session(['studio_locale' => $locale]);
                        $this->redirect(DynamicCollectionResource::getUrl('edit', [
                            'collection_slug' => $this->collectionSlug,
                            'record' => $this->getRecord(),
                        ]));
                    });
            }
        }

        $actions[] = Actions\ViewAction::make();
        $actions[] = Actions\DeleteAction::make();

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
                        ->map(fn ($cast) => $cast instanceof EavCast ? $cast->value : (string) $cast)
                        ->all();
                    $sensitiveFields = $fields->where('field_type', 'password')
                        ->pluck('column_name')
                        ->all();
                    $translatableFields = $fields->where('is_translatable', true)
                        ->pluck('column_name')
                        ->all();

                    return view('filament-studio::version-history', [
                        'versions' => $versions,
                        'fieldLabels' => $fieldLabels,
                        'fieldTypes' => $fieldTypes,
                        'sensitiveFields' => $sensitiveFields,
                        'translatableFields' => $translatableFields,
                        'showRestore' => true,
                    ]);
                });

            $actions[] = Actions\Action::make('restoreVersion')
                ->label('Restore')
                ->icon('heroicon-o-arrow-uturn-left')
                ->requiresConfirmation()
                ->modalHeading('Restore this version?')
                ->modalDescription('This will overwrite the current record values with the selected version snapshot. The current state will be saved as a new version before restoring.')
                ->action(function (array $arguments) {
                    /** @var StudioRecord $record */
                    $record = $this->getRecord();
                    $collection = $this->getResolvedCollection();

                    EavQueryBuilder::for($collection)
                        ->tenant($record->tenant_id)
                        ->restoreFromVersion($record->uuid, $arguments['versionId']);

                    $this->redirect($this->getUrl());
                })
                ->hidden();
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

    protected function getRedirectUrl(): string
    {
        return DynamicCollectionResource::getUrl('index', [
            'collection_slug' => $this->collectionSlug,
        ]);
    }
}
