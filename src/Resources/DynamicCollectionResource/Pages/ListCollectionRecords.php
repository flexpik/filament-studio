<?php

namespace Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Flexpik\FilamentStudio\Enums\FilterOperator;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Filtering\FilterGroup;
use Flexpik\FilamentStudio\Models\StudioSavedFilter;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Concerns\HasPanelWidgets;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Concerns\ResolvesCollection;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Database\Eloquent\Builder;

class ListCollectionRecords extends ListRecords
{
    use HasPanelWidgets;
    use ResolvesCollection;

    protected static string $resource = DynamicCollectionResource::class;

    /** @var array{logic: string, rules: array} */
    public array $advancedFilterTree = ['logic' => 'and', 'rules' => []];

    public function mount(): void
    {
        $this->initializeCollectionSlug();

        parent::mount();
    }

    public function getTitle(): string
    {
        return $this->getResolvedCollection()->label_plural;
    }

    /**
     * Apply an advanced filter tree from the Alpine.js filter builder.
     *
     * @param  array{logic: string, rules: array}  $tree
     */
    public function applyFilterTree(array $tree): void
    {
        $this->advancedFilterTree = $tree;
        $this->resetTable();
    }

    /**
     * Apply the advanced filter tree to the table query.
     */
    protected function applyAdvancedFilter(Builder $query): Builder
    {
        $tree = FilterGroup::fromArray($this->advancedFilterTree);

        if ($tree->isEmpty()) {
            return $query;
        }

        $collection = $this->getResolvedCollection();

        EavQueryBuilder::for($collection)
            ->applyFilterTree($tree)
            ->applyFilterToQuery($query);

        return $query;
    }

    protected function getHeaderActions(): array
    {
        $collection = $this->getResolvedCollection();

        return [
            Action::make('advancedFilter')
                ->label('Advanced Filter')
                ->icon('heroicon-o-funnel')
                ->color('gray')
                ->badge(fn () => $this->getActiveFilterCount())
                ->slideOver()
                ->modalContent(function () use ($collection) {
                    $fields = EavQueryBuilder::getCachedFields($collection);

                    $fieldOptions = $fields
                        ->where('is_filterable', true)
                        ->mapWithKeys(fn ($f) => [$f->column_name => $f->label ?? $f->column_name])
                        ->all();

                    $operatorsByField = $fields
                        ->where('is_filterable', true)
                        ->mapWithKeys(fn ($f) => [$f->column_name => FilterOperator::labelsForCast($f->eav_cast)])
                        ->all();

                    return view('filament-studio::livewire.filter-builder-alpine', [
                        'tree' => $this->advancedFilterTree,
                        'fieldOptions' => $fieldOptions,
                        'operatorsByField' => $operatorsByField,
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelAction(false),

            Action::make('saveFilter')
                ->label('Save Filter')
                ->icon('heroicon-o-bookmark')
                ->color('gray')
                ->visible(fn () => ! empty($this->advancedFilterTree['rules']))
                ->form([
                    TextInput::make('name')
                        ->label('Filter name')
                        ->required()
                        ->maxLength(255),
                    Toggle::make('is_shared')
                        ->label('Share with team')
                        ->helperText('When enabled, all team members can use this filter'),
                ])
                ->action(function (array $data) use ($collection) {
                    StudioSavedFilter::create([
                        'collection_id' => $collection->id,
                        'tenant_id' => Filament::getTenant()?->getKey(),
                        'created_by' => auth()->id(),
                        'name' => $data['name'],
                        'is_shared' => $data['is_shared'] ?? false,
                        'filter_tree' => $this->advancedFilterTree,
                    ]);

                    Notification::make()
                        ->title('Filter saved')
                        ->success()
                        ->send();
                }),

            Action::make('loadFilter')
                ->label('Saved Filters')
                ->icon('heroicon-o-bookmark-square')
                ->color('gray')
                ->form([
                    Select::make('filter_id')
                        ->label('Choose a saved filter')
                        ->options(function () use ($collection) {
                            return StudioSavedFilter::visibleTo(auth()->id() ?? 0)
                                ->forCollection($collection->id)
                                ->forTenant(Filament::getTenant()?->getKey())
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->required(),
                ])
                ->action(function (array $data) {
                    $filter = StudioSavedFilter::findOrFail($data['filter_id']);
                    $this->advancedFilterTree = $filter->filter_tree;
                    $this->resetPage();
                }),

            Actions\CreateAction::make()
                ->label('Create '.$collection->label)
                ->visible(fn (): bool => DynamicCollectionResource::canCreate()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return $this->buildWidgetsForPlacement(PanelPlacement::CollectionHeader);
    }

    protected function getFooterWidgets(): array
    {
        return $this->buildWidgetsForPlacement(PanelPlacement::CollectionFooter);
    }

    protected function getActiveFilterCount(): ?int
    {
        $count = count($this->advancedFilterTree['rules'] ?? []);

        return $count > 0 ? $count : null;
    }
}
