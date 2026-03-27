<div @class([
    'rounded-lg border border-gray-200 p-3 dark:border-gray-700',
    'ml-4 border-l-4 border-l-primary-500 dark:border-l-primary-400' => $depth > 0,
])>
    <div class="mb-2 flex items-center gap-2">
        <button
            type="button"
            wire:click="toggleLogic('{{ $path }}')"
            class="rounded-md bg-primary-100 px-2 py-1 text-xs font-semibold uppercase tracking-wider text-primary-700 transition dark:bg-primary-900 dark:text-primary-300"
        >
            {{ $group['logic'] === 'and' ? 'AND' : 'OR' }}
        </button>
        <span class="text-xs text-gray-500 dark:text-gray-400">
            {{ $group['logic'] === 'and' ? 'All conditions must match' : 'Any condition must match' }}
        </span>
    </div>

    <div class="space-y-2">
        @foreach ($group['rules'] ?? [] as $index => $item)
            @php
                $itemPath = $path === '' ? (string) $index : "{$path}.rules.{$index}";
            @endphp

            @if (isset($item['logic']))
                @include('filament-studio::livewire.partials.filter-group', [
                    'group' => $item,
                    'path' => $itemPath,
                    'fieldOptions' => $fieldOptions,
                    'depth' => $depth + 1,
                ])
            @else
                @include('filament-studio::livewire.partials.filter-rule-row', [
                    'rule' => $item,
                    'path' => $itemPath,
                    'fieldOptions' => $fieldOptions,
                ])
            @endif
        @endforeach
    </div>

    @if ($depth > 0)
        <div class="mt-2 flex items-center gap-2">
            <button type="button" wire:click="addRule('{{ $path }}')" class="text-xs text-primary-600 hover:underline dark:text-primary-400">
                + Rule
            </button>
            <button type="button" wire:click="addGroup('{{ $path }}')" class="text-xs text-primary-600 hover:underline dark:text-primary-400">
                + Group
            </button>
        </div>
    @endif
</div>
