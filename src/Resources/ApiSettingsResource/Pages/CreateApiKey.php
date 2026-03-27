<?php

namespace Flexpik\FilamentStudio\Resources\ApiSettingsResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Resources\ApiSettingsResource;
use Illuminate\Support\Str;

class CreateApiKey extends CreateRecord
{
    protected static string $resource = ApiSettingsResource::class;

    protected string $plainKey = '';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->plainKey = Str::random(64);
        $data['key'] = hash('sha256', $this->plainKey);

        $data['permissions'] = $this->buildPermissions($data);

        unset($data['wildcard_access'], $data['permission_entries']);

        return $data;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('API Key Created')
            ->body('Copy your API key now — it will not be shown again: **'.$this->plainKey.'**')
            ->success()
            ->persistent()
            ->send();
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
