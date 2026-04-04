<div class="fb__group {{ $depth > 0 ? 'fb__group--nested' : '' }}">
    <div class="fb__group-header">
        <button
            type="button"
            wire:click="toggleLogic('{{ $path }}')"
            class="fb__logic-btn"
        >
            {{ $group['logic'] === 'and' ? 'AND' : 'OR' }}
        </button>
        <span class="fb__logic-hint">
            {{ $group['logic'] === 'and' ? 'All conditions must match' : 'Any condition must match' }}
        </span>
    </div>

    <div class="fb__rules">
        @foreach ($group['rules'] ?? [] as $index => $item)
            @php
                $itemPath = $path === '' ? (string) $index : "{$path}.rules.{$index}";
            @endphp

            @if (isset($item['logic']))
                @include('filament-studio::livewire.partials.filter-group-inline', [
                    'group' => $item,
                    'path' => $itemPath,
                    'fieldOptions' => $fieldOptions,
                    'depth' => $depth + 1,
                ])
            @else
                @include('filament-studio::livewire.partials.filter-rule-row-inline', [
                    'rule' => $item,
                    'path' => $itemPath,
                    'fieldOptions' => $fieldOptions,
                ])
            @endif
        @endforeach
    </div>

    @if ($depth > 0)
        <div class="fb__subgroup-links">
            <button type="button" wire:click="addFilterRule('{{ $path }}')" class="fb__subgroup-link">
                + Rule
            </button>
            <button type="button" wire:click="addFilterGroup('{{ $path }}')" class="fb__subgroup-link">
                + Group
            </button>
        </div>
    @endif
</div>
