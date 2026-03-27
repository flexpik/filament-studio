<x-filament-widgets::widget>
    <x-filament::section>
        @php $interface = $this->config('interface', 'text'); @endphp

        <div class="space-y-2">
            @if($this->config('label'))
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $this->config('label') }}
                </label>
            @endif

            @if($interface === 'text')
                <x-filament::input.wrapper>
                    <x-filament::input type="text" wire:model.live.debounce.500ms="inputValue" />
                </x-filament::input.wrapper>
            @elseif($interface === 'number')
                <x-filament::input.wrapper>
                    <x-filament::input type="number" wire:model.live.debounce.500ms="inputValue" />
                </x-filament::input.wrapper>
            @elseif($interface === 'date')
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="inputValue" />
                </x-filament::input.wrapper>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
