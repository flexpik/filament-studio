<?php

namespace Flexpik\FilamentStudio\Resources\ApiSettingsResource\Pages;

use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Resources\ApiSettingsResource;
use Illuminate\Support\Str;

class EditApiKey extends EditRecord
{
    protected static string $resource = ApiSettingsResource::class;

    public ?string $newApiKey = null;

    public function mount(int|string $record): void
    {
        $this->newApiKey = session('new_api_key');

        parent::mount($record);
    }

    public function form(Schema $schema): Schema
    {
        $components = [];

        if ($this->newApiKey) {
            $components[] = Section::make('Your New API Key')
                ->description('Copy it now — it will not be shown again.')
                ->icon('heroicon-o-key')
                ->iconColor('success')
                ->schema([
                    Forms\Components\TextInput::make('_generated_api_key')
                        ->label('API Key')
                        ->default($this->newApiKey)
                        ->dehydrated(false)
                        ->readOnly()
                        ->extraInputAttributes([
                            'onclick' => 'this.select()',
                            'style' => 'font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;',
                        ])
                        ->suffixAction(
                            Forms\Components\TextInput\Actions\CopyAction::make()
                                ->copyMessage('Copied!')
                                ->copyMessageDuration(2000)
                        )
                        ->columnSpanFull(),
                ]);
        }

        $components[] = Section::make('API Key Details')
            ->description('Configure the name, status, and expiry for this API key.')
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Mobile App Key'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive keys will be rejected on all API requests.'),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->nullable()
                    ->helperText('Leave blank for a non-expiring key.'),
            ])
            ->columns(2);

        $components[] = Section::make('Permissions')
            ->description('Control which collections and actions this key can access.')
            ->schema([
                Forms\Components\Toggle::make('wildcard_access')
                    ->label('Full Access (Wildcard)')
                    ->helperText('Grant access to all collections and all actions.')
                    ->reactive()
                    ->default(false),

                Forms\Components\Repeater::make('permission_entries')
                    ->label('Collection Permissions')
                    ->hidden(fn (Get $get) => (bool) $get('wildcard_access'))
                    ->schema([
                        Forms\Components\Select::make('collection_slug')
                            ->label('Collection')
                            ->options(fn () => StudioCollection::query()->forTenant(Filament::getTenant()?->getKey())->pluck('label', 'slug')->toArray())
                            ->required()
                            ->searchable(),
                        Forms\Components\CheckboxList::make('actions')
                            ->label('Allowed Actions')
                            ->options(ApiAction::asSelectOptions())
                            ->columns(3),
                    ])
                    ->addActionLabel('Add Collection Permission')
                    ->collapsible()
                    ->itemLabel(fn (array $state) => $state['collection_slug'] ?? 'New Permission')
                    ->columnSpanFull(),
            ]);

        return $schema->columns(1)->components($components);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('regenerate_key')
                ->label('Regenerate Key')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Regenerate API Key')
                ->modalDescription('This will invalidate the current key immediately. Any applications using it will lose access.')
                ->modalSubmitActionLabel('Regenerate')
                ->action(function () {
                    $plainKey = Str::random(64);
                    $this->record->update(['key' => hash('sha256', $plainKey)]);

                    session()->flash('new_api_key', $plainKey);

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record]));
                }),
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

        if ($this->newApiKey) {
            $data['_generated_api_key'] = $this->newApiKey;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['permissions'] = $this->buildPermissions($data);

        unset($data['wildcard_access'], $data['permission_entries'], $data['_generated_api_key']);

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
