<?php

namespace Flexpik\FilamentStudio\Resources\CollectionManagerResource\Pages;

use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Flexpik\FilamentStudio\FilamentStudioPlugin;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioMigrationLog;
use Flexpik\FilamentStudio\Resources\CollectionManagerResource;
use Illuminate\Support\Str;

class CreateCollection extends CreateRecord
{
    use HasWizard;

    protected static string $resource = CollectionManagerResource::class;

    /** @return array<Wizard\Step> */
    protected function getSteps(): array
    {
        return [
            Wizard\Step::make('Basic Info')
                ->icon('heroicon-o-information-circle')
                ->description('Name and describe your collection')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(64)
                        ->regex('/^[a-z][a-z0-9_]*$/')
                        ->unique(StudioCollection::class, 'name')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            if ($state) {
                                $set('slug', Str::slug($state));
                                $set('label', Str::headline($state));
                                $set('label_plural', Str::plural(Str::headline($state)));
                            }
                        })
                        ->placeholder('e.g. blog_posts')
                        ->helperText('Unique identifier (snake_case). Auto-generates label and slug.'),
                    Forms\Components\TextInput::make('label')
                        ->required()
                        ->maxLength(128)
                        ->placeholder('e.g. Blog Post')
                        ->helperText('Singular display name shown in navigation and forms.'),
                    Forms\Components\TextInput::make('label_plural')
                        ->required()
                        ->maxLength(128)
                        ->placeholder('e.g. Blog Posts')
                        ->helperText('Plural display name used for list pages and breadcrumbs.'),
                    Forms\Components\Hidden::make('slug'),
                    Forms\Components\TextInput::make('icon')
                        ->maxLength(64)
                        ->placeholder('heroicon-o-table-cells')
                        ->helperText('Heroicon identifier for sidebar navigation. Browse icons at heroicons.com.'),
                    Forms\Components\Textarea::make('description')
                        ->maxLength(65535)
                        ->placeholder('Describe what this collection is used for...')
                        ->helperText('Internal description to help team members understand this collection\'s purpose.')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Wizard\Step::make('System Fields')
                ->icon('heroicon-o-cog-6-tooth')
                ->description('Choose which system fields to include')
                ->schema([
                    Forms\Components\CheckboxList::make('system_fields')
                        ->options([
                            'status' => 'Status — Draft/Published/Archived select field',
                            'sort_order' => 'Sort Order — Enables drag-and-drop reordering',
                            'created_by' => 'Created By — Auto-tracks who created each record',
                            'updated_by' => 'Updated By — Auto-tracks who last updated each record',
                            'timestamps' => 'Timestamps — Created At and Updated At fields',
                        ])
                        ->default(['timestamps'])
                        ->helperText('System fields are auto-populated and cannot be renamed. You can add more fields later.')
                        ->columnSpanFull(),
                ]),

            Wizard\Step::make('Settings')
                ->icon('heroicon-o-adjustments-horizontal')
                ->description('Configure collection behavior')
                ->schema([
                    Forms\Components\Toggle::make('is_singleton')
                        ->label('Singleton')
                        ->helperText('Limit to a single record (e.g. site settings).'),
                    Forms\Components\Toggle::make('enable_versioning')
                        ->label('Enable Versioning')
                        ->helperText('Keep a snapshot history of every record update.'),
                    Forms\Components\Toggle::make('enable_soft_deletes')
                        ->label('Enable Soft Deletes')
                        ->helperText('Move records to trash instead of permanent deletion.'),
                    Forms\Components\Select::make('sort_field')
                        ->options([
                            'created_at' => 'Created At',
                            'updated_at' => 'Updated At',
                            'sort_order' => 'Sort Order',
                        ])
                        ->placeholder('Default (created_at)')
                        ->helperText('Default sort field for record listing.'),
                    Forms\Components\Select::make('sort_direction')
                        ->options([
                            'asc' => 'Ascending',
                            'desc' => 'Descending',
                        ])
                        ->default('asc')
                        ->helperText('Default ordering direction for the record listing.'),
                    Forms\Components\TextInput::make('display_template')
                        ->maxLength(255)
                        ->placeholder('{{name}} — {{status}}')
                        ->helperText('Handlebars template for relationship dropdowns.')
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('multilingual_enabled')
                        ->label('Enable Multilingual')
                        ->helperText('Allow translatable fields to store values in multiple locales.')
                        ->live()
                        ->dehydrated(false)
                        ->visible(fn () => config('filament-studio.locales.enabled', false)),
                    Forms\Components\CheckboxList::make('supported_locales')
                        ->options(fn () => collect(config('filament-studio.locales.available', ['en']))
                            ->mapWithKeys(fn (string $locale) => [$locale => strtoupper($locale)])
                            ->all())
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => (bool) $get('multilingual_enabled'))
                        ->helperText('Select which locales this collection supports.')
                        ->columns(4),
                    Forms\Components\Select::make('default_locale')
                        ->options(fn () => collect(config('filament-studio.locales.available', ['en']))
                            ->mapWithKeys(fn (string $locale) => [$locale => strtoupper($locale)])
                            ->all())
                        ->placeholder('Use global default')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => (bool) $get('multilingual_enabled'))
                        ->helperText('The fallback locale when a translation is missing.'),
                ])
                ->columns(2),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        unset($data['system_fields'], $data['multilingual_enabled']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $systemFields = $this->data['system_fields'] ?? [];
        /** @var StudioCollection $collection */
        $collection = $this->record;
        $sortOrder = 0;

        $fieldDefinitions = $this->getSystemFieldDefinitions();

        foreach ($systemFields as $fieldKey) {
            if ($fieldKey === 'timestamps') {
                $this->createSystemField($collection, $fieldDefinitions['created_at'], $sortOrder++);
                $this->createSystemField($collection, $fieldDefinitions['updated_at'], $sortOrder++);
            } elseif (isset($fieldDefinitions[$fieldKey])) {
                $this->createSystemField($collection, $fieldDefinitions[$fieldKey], $sortOrder++);
            }
        }

        StudioMigrationLog::create([
            'tenant_id' => $collection->tenant_id,
            'collection_id' => $collection->id,
            'operation' => 'create_collection',
            'after_state' => $collection->toArray(),
            'performed_by' => auth()->id(),
        ]);

        FilamentStudioPlugin::fireAfterCollectionCreated($collection);
    }

    private function createSystemField(StudioCollection $collection, array $definition, int $sortOrder): void
    {
        StudioField::create([
            'collection_id' => $collection->id,
            'tenant_id' => $collection->tenant_id,
            'column_name' => $definition['column_name'],
            'label' => $definition['label'],
            'field_type' => $definition['field_type'],
            'eav_cast' => $definition['eav_cast'],
            'is_required' => $definition['is_required'] ?? false,
            'is_system' => true,
            'sort_order' => $sortOrder,
            'auto_fill_on' => $definition['auto_fill_on'] ?? null,
            'auto_fill_value' => $definition['auto_fill_value'] ?? null,
            'settings' => $definition['settings'] ?? null,
        ]);
    }

    /** @return array<string, array<string, mixed>> */
    private function getSystemFieldDefinitions(): array
    {
        return [
            'status' => [
                'column_name' => 'status',
                'label' => 'Status',
                'field_type' => 'select',
                'eav_cast' => 'text',
                'is_required' => true,
                'settings' => [
                    'options_source' => 'static',
                    'default_options' => [
                        ['value' => 'draft', 'label' => 'Draft'],
                        ['value' => 'published', 'label' => 'Published'],
                        ['value' => 'archived', 'label' => 'Archived'],
                    ],
                ],
            ],
            'sort_order' => [
                'column_name' => 'sort_order',
                'label' => 'Sort Order',
                'field_type' => 'integer',
                'eav_cast' => 'integer',
            ],
            'created_by' => [
                'column_name' => 'created_by',
                'label' => 'Created By',
                'field_type' => 'belongs_to',
                'eav_cast' => 'integer',
                'auto_fill_on' => ['create'],
                'auto_fill_value' => '$CURRENT_USER',
            ],
            'updated_by' => [
                'column_name' => 'updated_by',
                'label' => 'Updated By',
                'field_type' => 'belongs_to',
                'eav_cast' => 'integer',
                'auto_fill_on' => ['create', 'update'],
                'auto_fill_value' => '$CURRENT_USER',
            ],
            'created_at' => [
                'column_name' => 'created_at',
                'label' => 'Created At',
                'field_type' => 'datetime',
                'eav_cast' => 'datetime',
                'auto_fill_on' => ['create'],
                'auto_fill_value' => '$NOW',
            ],
            'updated_at' => [
                'column_name' => 'updated_at',
                'label' => 'Updated At',
                'field_type' => 'datetime',
                'eav_cast' => 'datetime',
                'auto_fill_on' => ['create', 'update'],
                'auto_fill_value' => '$NOW',
            ],
        ];
    }
}
