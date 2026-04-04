<div class="fb__rule" wire:key="rule-{{ $path }}">
    <select
        wire:model.live="advancedFilterTree.rules.{{ $path }}.field"
        class="fb__select"
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
            wire:model.live="advancedFilterTree.rules.{{ $path }}.operator"
            class="fb__select"
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
                wire:model.blur="advancedFilterTree.rules.{{ $path }}.value"
                placeholder="Value..."
                class="fb__input"
            />
        @endunless
    @endif

    <button
        type="button"
        wire:click="removeFilterRule('{{ $path }}')"
        class="fb__remove-btn"
        title="Remove rule"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
    </button>
</div>
