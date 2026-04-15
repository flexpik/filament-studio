<?php

namespace Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Concerns\ResolvesCollection;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Flexpik\FilamentStudio\Services\LocaleResolver;
use Illuminate\Database\Eloquent\Model;

class CreateCollectionRecord extends CreateRecord
{
    use ResolvesCollection;

    protected static string $resource = DynamicCollectionResource::class;

    public function mount(): void
    {
        $this->initializeCollectionSlug();
        DynamicCollectionResource::$currentPageContext = 'create';

        parent::mount();
    }

    public function getTitle(): string
    {
        return 'Create '.$this->getResolvedCollection()->label;
    }

    /**
     * Override the default create behavior to use EavQueryBuilder.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $collection = $this->getResolvedCollection();
        $tenantId = Filament::getTenant()?->getKey();
        $locale = app(LocaleResolver::class)->resolve($collection);

        $record = EavQueryBuilder::for($collection)
            ->tenant($tenantId)
            ->locale($locale)
            ->create($data, auth()->id());

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return DynamicCollectionResource::getUrl('index', [
            'collection_slug' => $this->collectionSlug,
        ]);
    }
}
