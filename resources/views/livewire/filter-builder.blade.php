<div class="space-y-3">
    @include('filament-studio::livewire.partials.filter-group', [
        'group' => $tree,
        'path' => '',
        'fieldOptions' => $fieldOptions,
        'depth' => 0,
    ])

    <div class="flex items-center gap-2 pt-2">
        <button type="button" wire:click="addRule" class="fi-btn fi-btn-size-sm rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-600">
            Add Rule
        </button>

        <button type="button" wire:click="addGroup" class="fi-btn fi-btn-size-sm rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-600">
            Add Group
        </button>

        <div class="ml-auto flex items-center gap-2">
            <button type="button" wire:click="clearFilter" class="fi-btn fi-btn-size-sm rounded-lg px-3 py-1.5 text-sm font-medium text-danger-600 hover:text-danger-500 dark:text-danger-400">
                Clear
            </button>

            <button type="button" wire:click="applyFilter" class="fi-btn fi-btn-size-sm rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-primary-500">
                Apply Filter
            </button>
        </div>
    </div>
</div>
