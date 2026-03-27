<div class="flex items-center gap-2" wire:key="rule-{{ $path }}">
    <select
        wire:model.live="tree.rules.{{ $path }}.field"
        class="min-w-[140px] rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
    >
        <option value="">Select field...</option>
        @foreach ($fieldOptions as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>

    @if (!empty($rule['field']))
        @php
            $operators = $this->getOperatorsForField($rule['field']);
        @endphp
        <select
            wire:model.live="tree.rules.{{ $path }}.operator"
            class="min-w-[140px] rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
        >
            @foreach ($operators as $opValue => $opLabel)
                <option value="{{ $opValue }}">{{ $opLabel }}</option>
            @endforeach
        </select>
    @endif

    @if (!empty($rule['field']) && !empty($rule['operator']))
        @php
            $op = \Flexpik\FilamentStudio\Enums\FilterOperator::tryFrom($rule['operator']);
            $isUnary = $op?->isUnary() ?? false;
        @endphp

        @unless ($isUnary)
            <input
                type="text"
                wire:model.blur="tree.rules.{{ $path }}.value"
                placeholder="Value..."
                class="min-w-[140px] rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
            />
        @endunless
    @endif

    <button
        type="button"
        wire:click="removeRule('{{ $path }}')"
        class="text-danger-500 hover:text-danger-700 dark:text-danger-400"
        title="Remove rule"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
    </button>
</div>
