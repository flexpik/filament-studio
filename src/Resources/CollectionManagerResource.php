<?php

namespace Flexpik\FilamentStudio\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Resources\CollectionManagerResource\Pages;
use Illuminate\Database\Eloquent\Builder;
use Flexpik\FilamentStudio\Resources\CollectionManagerResource\RelationManagers\FieldsRelationManager;
use Flexpik\FilamentStudio\Resources\CollectionManagerResource\RelationManagers\MigrationLogsRelationManager;

class CollectionManagerResource extends Resource
{
    protected static ?string $model = StudioCollection::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Data Models';

    protected static string|\UnitEnum|null $navigationGroup = 'Studio';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'studio/schema';

    protected static ?string $modelLabel = 'Data Model';

    protected static ?string $pluralModelLabel = 'Data Models';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', StudioCollection::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', StudioCollection::class) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update', $record) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete', $record) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Basic Info')
                            ->description('Core identity settings for this collection.')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(64)
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('The internal identifier cannot be changed after creation.'),
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
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(64)
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('URL-safe identifier. Auto-generated from the name.'),
                                Forms\Components\TextInput::make('icon')
                                    ->maxLength(64)
                                    ->placeholder('heroicon-o-table-cells')
                                    ->helperText('Heroicon identifier for sidebar navigation. Browse icons at heroicons.com.'),
                                Forms\Components\Textarea::make('description')
                                    ->maxLength(65535)
                                    ->placeholder('Describe what this collection is used for...')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ]),

                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Behavior')
                            ->description('Toggle features that affect how records in this collection behave.')
                            ->schema([
                                Forms\Components\Toggle::make('is_singleton')
                                    ->label('Singleton')
                                    ->helperText('Limit to a single record (e.g. site settings).'),
                                Forms\Components\Toggle::make('is_hidden')
                                    ->label('Hidden from Navigation')
                                    ->helperText('Hide this collection from the sidebar. Records are still accessible via direct URL.'),
                                Forms\Components\Toggle::make('enable_versioning')
                                    ->label('Enable Versioning')
                                    ->helperText('Keep a snapshot history of every record update.'),
                                Forms\Components\Toggle::make('enable_soft_deletes')
                                    ->label('Enable Soft Deletes')
                                    ->helperText('Move records to trash instead of permanent deletion.'),
                            ])
                            ->collapsible()
                            ->collapsed(),

                        Section::make('Display & Sorting')
                            ->description('Control how records are sorted and displayed in relationship dropdowns.')
                            ->schema([
                                Forms\Components\Select::make('sort_field')
                                    ->options(function (?StudioCollection $record) {
                                        if (! $record) {
                                            return [];
                                        }

                                        return $record->fields()
                                            ->pluck('label', 'column_name')
                                            ->toArray();
                                    })
                                    ->placeholder('Default (created_at)')
                                    ->helperText('The field used to sort records in the list view.'),
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
                                    ->helperText('Handlebars template for relationship dropdowns. Use {{field_name}} to reference field values.'),
                            ])
                            ->collapsible()
                            ->collapsed(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->forTenant(Filament::getTenant()?->getKey()))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fields_count')
                    ->counts('fields')
                    ->label('Fields')
                    ->sortable(),
                Tables\Columns\TextColumn::make('records_count')
                    ->counts('records')
                    ->label('Records')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_singleton')
                    ->boolean()
                    ->label('Singleton'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_singleton'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('label');
    }

    public static function getRelations(): array
    {
        return [
            FieldsRelationManager::class,
            MigrationLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCollections::route('/'),
            'create' => Pages\CreateCollection::route('/create'),
            'edit' => Pages\EditCollection::route('/{record}/edit'),
        ];
    }
}
