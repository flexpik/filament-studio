<?php

namespace Flexpik\FilamentStudio\Resources;

use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Flexpik\FilamentStudio\Filtering\FilterGroup;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Pages;
use Flexpik\FilamentStudio\Services\DynamicFiltersBuilder;
use Flexpik\FilamentStudio\Services\DynamicFormSchemaBuilder;
use Flexpik\FilamentStudio\Services\DynamicTableColumnsBuilder;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DynamicCollectionResource extends Resource
{
    protected static ?string $model = StudioRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'studio/{collection_slug}';

    protected static ?string $currentCollectionSlug = null;

    protected static ?StudioCollection $currentCollection = null;

    public static ?string $currentPageContext = null;

    /**
     * Resolve the current collection from the route's {collection_slug} parameter,
     * scoped to the current tenant when available.
     */
    public static function resolveCollection(?string $slug = null): StudioCollection
    {
        $slug ??= request()->route('collection_slug') ?? static::$currentCollectionSlug;

        // Return cached if same slug
        if (static::$currentCollection && static::$currentCollectionSlug === $slug) {
            return static::$currentCollection;
        }

        static::$currentCollectionSlug = $slug;
        static::$currentCollection = StudioCollection::query()
            ->forTenant(Filament::getTenant()?->getKey())
            ->where('slug', $slug)
            ->firstOrFail();

        return static::$currentCollection;
    }

    /**
     * Reset the cached collection (useful in tests).
     */
    public static function resetResolvedCollection(): void
    {
        static::$currentCollectionSlug = null;
        static::$currentCollection = null;
    }

    public static function canViewAny(): bool
    {
        $collection = static::resolveCollectionOrNull();
        if (! $collection) {
            return true;
        }

        return auth()->user()?->can('viewRecords', $collection) ?? false;
    }

    public static function canCreate(): bool
    {
        $collection = static::resolveCollectionOrNull();
        if (! $collection) {
            return false;
        }

        return auth()->user()?->can('createRecord', $collection) ?? false;
    }

    public static function canEdit($record): bool
    {
        $collection = static::resolveCollectionOrNull();
        if (! $collection) {
            return false;
        }

        return auth()->user()?->can('updateRecord', $collection) ?? false;
    }

    public static function canDelete($record): bool
    {
        $collection = static::resolveCollectionOrNull();
        if (! $collection) {
            return false;
        }

        return auth()->user()?->can('deleteRecord', $collection) ?? false;
    }

    /**
     * Resolve collection without throwing — returns null if not in a route context.
     */
    protected static function resolveCollectionOrNull(): ?StudioCollection
    {
        try {
            return static::resolveCollection();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getModelLabel(): string
    {
        return static::resolveCollectionOrNull()?->label ?? 'Record';
    }

    public static function getPluralModelLabel(): string
    {
        return static::resolveCollectionOrNull()?->label_plural ?? 'Records';
    }

    public static function getNavigationLabel(): string
    {
        return static::resolveCollectionOrNull()?->label_plural ?? 'Records';
    }

    public static function form(Schema $schema): Schema
    {
        $collection = static::resolveCollection();
        $formSchema = DynamicFormSchemaBuilder::build(
            $collection,
            static::$currentPageContext,
            auth()->user(),
        );

        return $schema->components($formSchema);
    }

    public static function table(Table $table): Table
    {
        $collection = static::resolveCollection();

        return $table
            ->query(
                EavQueryBuilder::for($collection)
                    ->tenant(Filament::getTenant()?->getKey())
                    ->toEloquentQuery()
            )
            ->modifyQueryUsing(function (Builder $query) use ($collection, $table) {
                $livewire = $table->getLivewire();

                if (property_exists($livewire, 'advancedFilterTree')) {
                    $tree = FilterGroup::fromArray($livewire->advancedFilterTree);

                    if (! $tree->isEmpty()) {
                        EavQueryBuilder::for($collection)
                            ->applyFilterTree($tree)
                            ->applyFilterToQuery($query);
                    }
                }

                return $query;
            })
            ->columns(DynamicTableColumnsBuilder::build($collection))
            ->filters(DynamicFiltersBuilder::build($collection))
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => static::canEdit(null)),
                DeleteAction::make()
                    ->visible(fn (): bool => static::canDelete(null)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => static::canDelete(null)),
                ]),
            ]);
    }

    /**
     * Resolve a record by UUID instead of integer ID.
     */
    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $collection = static::resolveCollection();

        $query = StudioRecord::query()
            ->where(fn ($q) => $q->where('uuid', $key)->orWhere('id', $key))
            ->where('collection_id', $collection->id)
            ->forTenant(Filament::getTenant()?->getKey());

        if ($modifyQuery) {
            $modifyQuery($query);
        }

        return $query->first();
    }

    public static function getRecordRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false, ?string $configuration = null): string
    {
        if (! isset($parameters['collection_slug'])) {
            $parameters['collection_slug'] = request()->route('collection_slug')
                ?? static::$currentCollectionSlug
                ?? '';
        }

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters, $configuration);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCollectionRecords::route('/'),
            'create' => Pages\CreateCollectionRecord::route('/create'),
            'view' => Pages\ViewCollectionRecord::route('/{record}'),
            'edit' => Pages\EditCollectionRecord::route('/{record}/edit'),
        ];
    }
}
