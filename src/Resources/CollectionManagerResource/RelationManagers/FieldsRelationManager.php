<?php

namespace Flexpik\FilamentStudio\Resources\CollectionManagerResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Flexpik\FilamentStudio\FieldTypes\FieldTypeRegistry;
use Flexpik\FilamentStudio\FilamentStudioPlugin;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioMigrationLog;
use Flexpik\FilamentStudio\Services\ConditionEvaluator;
use Illuminate\Database\Eloquent\Model;

class FieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'fields';

    protected static ?string $title = 'Fields';

    protected ?array $beforeFieldState = null;

    public function table(Table $table): Table
    {
        $registry = app(FieldTypeRegistry::class);
        $fieldTypeOptions = collect($registry->all())
            ->mapWithKeys(fn ($class, $key) => [$key => $class::$label])
            ->toArray();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('column_name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('label')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('field_type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('eav_cast')
                    ->badge()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_required')
                    ->boolean()
                    ->label('Required'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('field_type')
                    ->options($fieldTypeOptions),
                Tables\Filters\TernaryFilter::make('is_required'),
            ])
            ->reorderable('sort_order')
            ->afterReordering(function ($livewire) {
                $ownerRecord = $livewire->getOwnerRecord();
                $fields = $ownerRecord->fields()->orderBy('sort_order')->get();

                $fieldPositions = $fields->pluck('sort_order', 'column_name');

                foreach ($fields as $field) {
                    $conditions = $field->settings['conditions'] ?? [];

                    foreach (['visible', 'required', 'disabled'] as $target) {
                        $rules = $conditions[$target]['rules'] ?? [];
                        if (! is_array($rules)) {
                            continue;
                        }

                        foreach ($rules as $rule) {
                            if (($rule['type'] ?? '') !== 'field_value') {
                                continue;
                            }

                            $refField = $rule['field'] ?? '';
                            $refPosition = $fieldPositions[$refField] ?? null;

                            if ($refPosition !== null && $refPosition > $field->sort_order) {
                                Notification::make()
                                    ->title('Forward reference warning')
                                    ->body("Field \"{$field->label}\" has a condition referencing \"{$refField}\" which now appears after it in the form.")
                                    ->warning()
                                    ->send();
                            }
                        }
                    }
                }
            })
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make()
                    ->before(function (Model $record) {
                        $this->beforeFieldState = $record->toArray();
                    })
                    ->after(function (Model $record) {
                        /** @var StudioField $record */
                        StudioMigrationLog::create([
                            'tenant_id' => $record->collection->tenant_id,
                            'collection_id' => $record->collection_id,
                            'field_id' => $record->id,
                            'operation' => 'update_field',
                            'before_state' => $this->beforeFieldState ?? null,
                            'after_state' => $record->fresh()->toArray(),
                            'performed_by' => auth()->id(),
                        ]);

                        $allFields = $record->collection->fields()->orderBy('sort_order')->get();
                        $cycle = ConditionEvaluator::detectCycles($allFields);

                        if ($cycle !== null) {
                            Notification::make()
                                ->title('Circular dependency detected')
                                ->body('Fields '.implode(' → ', $cycle).' form a circular dependency. Please fix the conditions.')
                                ->danger()
                                ->send();
                        }
                    }),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->before(function (Model $record) {
                        /** @var StudioField $record */
                        StudioMigrationLog::create([
                            'tenant_id' => $record->collection->tenant_id,
                            'collection_id' => $record->collection_id,
                            'field_id' => $record->id,
                            'operation' => 'delete_field',
                            'before_state' => $record->toArray(),
                            'after_state' => null,
                            'performed_by' => auth()->id(),
                        ]);
                    }),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        /** @var StudioCollection $ownerRecord */
                        $ownerRecord = $this->getOwnerRecord();
                        $maxSortOrder = $ownerRecord->fields()->max('sort_order') ?? -1;

                        $data['sort_order'] = $maxSortOrder + 1;
                        $data['collection_id'] = $ownerRecord->id;
                        $data['tenant_id'] = $ownerRecord->tenant_id;

                        return $data;
                    })
                    ->after(function (Model $record) {
                        /** @var StudioField $record */
                        /** @var StudioCollection $owner */
                        $owner = $this->getOwnerRecord();
                        StudioMigrationLog::create([
                            'tenant_id' => $record->collection->tenant_id ?? $owner->tenant_id,
                            'collection_id' => $record->collection_id,
                            'field_id' => $record->id,
                            'operation' => 'add_field',
                            'before_state' => null,
                            'after_state' => $record->toArray(),
                            'performed_by' => auth()->id(),
                        ]);

                        FilamentStudioPlugin::fireAfterFieldAdded($record);

                        $allFields = $record->collection->fields()->orderBy('sort_order')->get();
                        $cycle = ConditionEvaluator::detectCycles($allFields);

                        if ($cycle !== null) {
                            Notification::make()
                                ->title('Circular dependency detected')
                                ->body('Fields '.implode(' → ', $cycle).' form a circular dependency. Please fix the conditions.')
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        $registry = app(FieldTypeRegistry::class);

        return $schema->components([
            Section::make('Field Identity')
                ->icon('heroicon-o-identification')
                ->description('Define the field type and its internal identifier.')
                ->schema([
                    Forms\Components\Select::make('field_type')
                        ->required()
                        ->options(function () use ($registry) {
                            $grouped = [];
                            foreach ($registry->all() as $key => $class) {
                                $category = ucfirst($class::$category);
                                $grouped[$category][$key] = $class::$label;
                            }
                            ksort($grouped);

                            return $grouped;
                        })
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state) use ($registry) {
                            if ($state) {
                                $allTypes = $registry->all();
                                if (isset($allTypes[$state])) {
                                    $set('eav_cast', $allTypes[$state]::$eavCast->value);
                                }
                            }
                        })
                        ->helperText('Determines the input type, storage format, and available settings.'),
                    Forms\Components\TextInput::make('column_name')
                        ->required()
                        ->maxLength(64)
                        ->regex('/^[a-z][a-z0-9_]*$/')
                        ->placeholder('e.g. featured_image')
                        ->helperText('Internal identifier (snake_case). Used as the storage key.'),
                    Forms\Components\TextInput::make('label')
                        ->required()
                        ->maxLength(128)
                        ->placeholder('e.g. Featured Image')
                        ->helperText('Display label shown to users in forms and tables.'),
                    Forms\Components\Hidden::make('eav_cast'),
                ])
                ->columns(2),

            Section::make('Behavior')
                ->icon('heroicon-o-cog-6-tooth')
                ->description('Control validation and visibility defaults for this field.')
                ->schema([
                    Forms\Components\Toggle::make('is_required')
                        ->label('Required')
                        ->helperText('Users must fill in this field before saving.'),
                    Forms\Components\Toggle::make('is_unique')
                        ->label('Unique')
                        ->helperText('No two records can have the same value for this field.'),
                    Forms\Components\Toggle::make('is_indexed')
                        ->label('Indexed')
                        ->helperText('Improves search and filter performance for this field.'),
                    Forms\Components\Toggle::make('is_hidden_in_form')
                        ->label('Hidden in Form')
                        ->helperText('Hide this field from the create and edit forms.'),
                    Forms\Components\Toggle::make('is_hidden_in_table')
                        ->label('Hidden in Table')
                        ->helperText('Hide this field from the record listing table.'),
                    Forms\Components\Toggle::make('is_translatable')
                        ->label('Translatable')
                        ->helperText('Store separate values per locale for this field.')
                        ->visible(function () {
                            if (! config('filament-studio.locales.enabled', false)) {
                                return false;
                            }
                            $collection = $this->getOwnerRecord();

                            return $collection instanceof StudioCollection && ! empty($collection->supported_locales);
                        }),
                ])
                ->columns(2),

            Section::make('Presentation')
                ->icon('heroicon-o-paint-brush')
                ->description('Customize how this field appears to end users in the data entry form.')
                ->schema([
                    Forms\Components\Select::make('width')
                        ->options([
                            'full' => 'Full Width',
                            'half' => 'Half Width',
                            'third' => 'One Third',
                            'two_thirds' => 'Two Thirds',
                        ])
                        ->default('full')
                        ->helperText('Controls how much horizontal space this field occupies.'),
                    Forms\Components\TextInput::make('placeholder')
                        ->maxLength(255)
                        ->placeholder('e.g. Enter your value here...')
                        ->helperText('Ghost text shown inside the input when empty. Not saved as data.'),
                    Forms\Components\TextInput::make('hint')
                        ->maxLength(255)
                        ->placeholder('e.g. Maximum 500 characters')
                        ->helperText('Small helper text displayed next to the field label.'),
                    Forms\Components\TextInput::make('default_value')
                        ->maxLength(255)
                        ->placeholder('e.g. draft')
                        ->helperText('Pre-filled value when creating a new record. Leave empty for no default.'),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            Section::make('Type-Specific Settings')
                ->icon('heroicon-o-wrench-screwdriver')
                ->description('Configure additional options specific to the selected field type, such as formatting, constraints, and display behavior.')
                ->schema(function (Get $get) use ($registry): array {
                    $fieldType = $get('field_type');

                    if (! $fieldType) {
                        return [];
                    }

                    $allTypes = $registry->all();

                    if (! isset($allTypes[$fieldType])) {
                        return [];
                    }

                    return $allTypes[$fieldType]::settingsSchema();
                })
                ->collapsible()
                ->collapsed()
                ->visible(fn (Get $get) => filled($get('field_type'))),

            Section::make('Conditions')
                ->icon('heroicon-o-adjustments-horizontal')
                ->description('Define rules that dynamically control this field\'s visibility, required state, or disabled state based on other field values, permissions, or record context.')
                ->columnSpanFull()
                ->schema([
                    Tabs::make('conditions')
                        ->columnSpanFull()
                        ->tabs([
                            Tabs\Tab::make('Visibility')
                                ->icon('heroicon-o-eye')
                                ->badge(fn (Get $get) => count($get('settings.conditions.visible.rules') ?? []) ?: null)
                                ->badgeColor('info')
                                ->schema(static::conditionTabSchema('visible')),
                            Tabs\Tab::make('Required')
                                ->icon('heroicon-o-exclamation-circle')
                                ->badge(fn (Get $get) => count($get('settings.conditions.required.rules') ?? []) ?: null)
                                ->badgeColor('warning')
                                ->schema(static::conditionTabSchema('required')),
                            Tabs\Tab::make('Disabled')
                                ->icon('heroicon-o-lock-closed')
                                ->badge(fn (Get $get) => count($get('settings.conditions.disabled.rules') ?? []) ?: null)
                                ->badgeColor('danger')
                                ->schema(static::conditionTabSchema('disabled')),
                        ]),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }

    /**
     * Build the condition rule builder schema for a single condition target tab.
     */
    protected static function conditionTabSchema(string $target): array
    {
        return [
            Forms\Components\Placeholder::make("conditions_{$target}_hint")
                ->content(match ($target) {
                    'visible' => 'Control when this field is shown or hidden in the form. Add rules to make the field appear only when certain conditions are met.',
                    'required' => 'Make this field conditionally required based on other field values, user permissions, or record context.',
                    'disabled' => 'Conditionally disable this field to prevent editing while still displaying it.',
                })
                ->visible(fn (Get $get) => count($get("settings.conditions.{$target}.rules") ?? []) === 0),
            Forms\Components\ToggleButtons::make("settings.conditions.{$target}.logic")
                ->options(['and' => 'All rules must match (AND)', 'or' => 'Any rule must match (OR)'])
                ->default('and')
                ->visible(fn (Get $get) => count($get("settings.conditions.{$target}.rules") ?? []) > 0)
                ->inline()
                ->helperText('Determines how multiple rules combine — AND requires all rules to pass, OR requires at least one.'),
            Forms\Components\Repeater::make("settings.conditions.{$target}.rules")
                ->schema([
                    Forms\Components\Select::make('type')
                        ->options(function () {
                            $options = [
                                'field_value' => 'Field Value',
                                'permission' => 'Permission Gate',
                                'record_state' => 'Record State',
                            ];

                            if (count(ConditionEvaluator::getRegisteredResolverKeys()) > 0) {
                                $options['external'] = 'External Resolver';
                            }

                            return $options;
                        })
                        ->required()
                        ->live()
                        ->columnSpan(2)
                        ->helperText('Choose what triggers this condition rule.'),

                    Forms\Components\Select::make('field')
                        ->label('Target Field')
                        ->options(function (Get $get, $livewire) {
                            $ownerRecord = $livewire->getOwnerRecord();
                            $currentSortOrder = $get('../../sort_order') ?? PHP_INT_MAX;

                            return $ownerRecord->fields()
                                ->where('sort_order', '<', $currentSortOrder)
                                ->orderBy('sort_order')
                                ->pluck('label', 'column_name')
                                ->toArray();
                        })
                        ->searchable()
                        ->visible(fn (Get $get) => $get('type') === 'field_value')
                        ->required(fn (Get $get) => $get('type') === 'field_value')
                        ->live()
                        ->helperText('Only fields positioned before this one are available.'),
                    Forms\Components\Select::make('op')
                        ->label('Operator')
                        ->options([
                            'equals' => 'Equals',
                            'not_equals' => 'Not Equals',
                            'in' => 'In',
                            'not_in' => 'Not In',
                            'is_empty' => 'Is Empty',
                            'is_not_empty' => 'Is Not Empty',
                            'greater_than' => 'Greater Than',
                            'less_than' => 'Less Than',
                            'contains' => 'Contains',
                        ])
                        ->visible(fn (Get $get) => $get('type') === 'field_value')
                        ->required(fn (Get $get) => $get('type') === 'field_value')
                        ->live(),

                    Forms\Components\Select::make('value')
                        ->label('Value')
                        ->options(function (Get $get, $livewire) {
                            $fieldName = $get('field');
                            if (! $fieldName) {
                                return [];
                            }

                            $ownerRecord = $livewire->getOwnerRecord();
                            $targetField = $ownerRecord->fields()
                                ->where('column_name', $fieldName)
                                ->first();

                            if (! $targetField || ! in_array($targetField->field_type, ['select', 'radio', 'checkbox_list'])) {
                                return [];
                            }

                            return $targetField->options()
                                ->orderBy('sort_order')
                                ->pluck('label', 'value')
                                ->toArray();
                        })
                        ->visible(function (Get $get, $livewire) {
                            if ($get('type') !== 'field_value') {
                                return false;
                            }
                            if (in_array($get('op'), ['is_empty', 'is_not_empty'])) {
                                return false;
                            }

                            $fieldName = $get('field');
                            if (! $fieldName) {
                                return false;
                            }

                            $ownerRecord = $livewire->getOwnerRecord();
                            $targetField = $ownerRecord->fields()->where('column_name', $fieldName)->first();

                            return $targetField && in_array($targetField->field_type, ['select', 'radio', 'checkbox_list']);
                        }),
                    Forms\Components\TextInput::make('value')
                        ->label('Value')
                        ->visible(function (Get $get, $livewire) {
                            if ($get('type') !== 'field_value') {
                                return false;
                            }
                            if (in_array($get('op'), ['is_empty', 'is_not_empty'])) {
                                return false;
                            }

                            $fieldName = $get('field');
                            if (! $fieldName) {
                                return true;
                            }

                            $ownerRecord = $livewire->getOwnerRecord();
                            $targetField = $ownerRecord->fields()->where('column_name', $fieldName)->first();

                            return ! $targetField || ! in_array($targetField->field_type, ['select', 'radio', 'checkbox_list']);
                        }),

                    Forms\Components\TextInput::make('gate')
                        ->label('Gate Name')
                        ->visible(fn (Get $get) => $get('type') === 'permission')
                        ->required(fn (Get $get) => $get('type') === 'permission')
                        ->placeholder('e.g. manage-settings')
                        ->helperText('Laravel authorization gate name to check.'),
                    Forms\Components\Toggle::make('negate')
                        ->label('Negate (invert result)')
                        ->visible(fn (Get $get) => $get('type') === 'permission')
                        ->helperText('If enabled, the rule matches when the gate check fails.'),

                    Forms\Components\Select::make('state')
                        ->options(['create' => 'Create', 'edit' => 'Edit'])
                        ->visible(fn (Get $get) => $get('type') === 'record_state')
                        ->required(fn (Get $get) => $get('type') === 'record_state')
                        ->helperText('Apply this rule only when creating or editing a record.'),

                    Forms\Components\Select::make('resolver')
                        ->options(fn () => array_combine(
                            ConditionEvaluator::getRegisteredResolverKeys(),
                            ConditionEvaluator::getRegisteredResolverKeys()
                        ))
                        ->visible(fn (Get $get) => $get('type') === 'external')
                        ->required(fn (Get $get) => $get('type') === 'external')
                        ->helperText('Select a registered custom condition resolver.'),
                    Forms\Components\Toggle::make('reactive')
                        ->label('Re-evaluate on every change')
                        ->visible(fn (Get $get) => $get('type') === 'external')
                        ->helperText('When enabled, this resolver re-evaluates as the user types. May impact performance.'),
                ])
                ->columns(2)
                ->defaultItems(0)
                ->addActionLabel('Add Rule')
                ->collapsible()
                ->itemLabel(function (array $state): string {
                    return match ($state['type'] ?? null) {
                        'field_value' => sprintf(
                            'When %s %s %s',
                            $state['field'] ?? '…',
                            str_replace('_', ' ', $state['op'] ?? '…'),
                            $state['value'] ?? '',
                        ),
                        'permission' => sprintf(
                            'Gate: %s%s',
                            $state['gate'] ?? '…',
                            ($state['negate'] ?? false) ? ' (negated)' : '',
                        ),
                        'record_state' => sprintf('On %s', ucfirst($state['state'] ?? '…')),
                        'external' => sprintf(
                            'Resolver: %s%s',
                            $state['resolver'] ?? '…',
                            ($state['reactive'] ?? false) ? ' (live)' : '',
                        ),
                        default => 'New Rule',
                    };
                }),
        ];
    }
}
