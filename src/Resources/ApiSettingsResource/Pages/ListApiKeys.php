<?php

namespace Flexpik\FilamentStudio\Resources\ApiSettingsResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Flexpik\FilamentStudio\Resources\ApiSettingsResource;

class ListApiKeys extends ListRecords
{
    protected static string $resource = ApiSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('api_documentation')
                ->label('API Documentation')
                ->icon('heroicon-o-book-open')
                ->color('info')
                ->url(route('scramble.docs.ui'), shouldOpenInNewTab: true),
            Actions\CreateAction::make(),
        ];
    }
}
