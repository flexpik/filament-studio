<?php

namespace Flexpik\FilamentStudio\Resources\CollectionManagerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Flexpik\FilamentStudio\Resources\CollectionManagerResource;

class ListCollections extends ListRecords
{
    protected static string $resource = CollectionManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
