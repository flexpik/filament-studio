<x-filament-widgets::widget>
    <div @class([
        'px-4 py-2',
        match($this->config('text_size', 'lg')) {
            'sm' => 'text-sm',
            'base' => 'text-base',
            'lg' => 'text-lg',
            'xl' => 'text-xl',
            '2xl' => 'text-2xl',
            default => 'text-lg',
        },
        'font-semibold',
    ])
    @if($this->config('text_color'))
        style="color: {{ $this->config('text_color') }}"
    @endif
    >
        {{ $this->config('text', '') }}
    </div>
</x-filament-widgets::widget>
