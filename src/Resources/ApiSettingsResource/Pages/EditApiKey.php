<?php

namespace Flexpik\FilamentStudio\Resources\ApiSettingsResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Resources\ApiSettingsResource;

class EditApiKey extends EditRecord
{
    protected static string $resource = ApiSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $permissions = $data['permissions'] ?? [];

        if (isset($permissions['*'])) {
            $data['wildcard_access'] = true;
            $data['permission_entries'] = [];
        } else {
            $data['wildcard_access'] = false;
            $data['permission_entries'] = collect($permissions)
                ->map(fn (array $actions, string $slug) => [
                    'collection_slug' => $slug,
                    'actions' => $actions,
                ])
                ->values()
                ->all();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['permissions'] = $this->buildPermissions($data);

        unset($data['wildcard_access'], $data['permission_entries']);

        return $data;
    }

    /**
     * Build the permissions array from form data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array<string>>
     */
    protected function buildPermissions(array $data): array
    {
        if (! empty($data['wildcard_access'])) {
            return ['*' => collect(ApiAction::cases())->map(fn (ApiAction $a) => $a->value)->all()];
        }

        $permissions = [];

        foreach ($data['permission_entries'] ?? [] as $entry) {
            $slug = $entry['collection_slug'] ?? null;
            $actions = $entry['actions'] ?? [];

            if ($slug && ! empty($actions)) {
                $permissions[$slug] = array_values($actions);
            }
        }

        return $permissions;
    }
}
