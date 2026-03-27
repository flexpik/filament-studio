<x-filament-widgets::widget>
    <x-filament::section>
        @if($this->panel->header_visible && $this->panel->header_label)
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                {{ $this->panel->header_label }}
            </div>
        @endif
        @php
            $percentage = $this->getPercentage();
            $size = $this->config('size', 'full') === 'half' ? 'half' : 'full';
            $strokeWidth = (int) $this->config('stroke_width', 10);
            $color = $this->config('color', '#3b82f6');
            $rounded = $this->config('rounded_stroke', true);
            $radius = 45;
            $circumference = $size === 'half' ? $radius * M_PI : 2 * $radius * M_PI;
            $offset = $circumference - ($percentage / 100) * $circumference;
        @endphp
        <div class="flex flex-col items-center">
            <svg viewBox="0 0 100 {{ $size === 'half' ? '55' : '100' }}" class="w-full max-w-[200px]">
                <circle
                    cx="50" cy="50" r="{{ $radius }}"
                    fill="none"
                    stroke="currentColor"
                    class="text-gray-200 dark:text-gray-700"
                    stroke-width="{{ $strokeWidth }}"
                    @if($size === 'half') stroke-dasharray="{{ $radius * M_PI }} {{ 2 * $radius * M_PI }}" transform="rotate(180, 50, 50)" @endif
                />
                <circle
                    cx="50" cy="50" r="{{ $radius }}"
                    fill="none"
                    stroke="{{ $color }}"
                    stroke-width="{{ $strokeWidth }}"
                    stroke-dasharray="{{ $circumference }}"
                    stroke-dashoffset="{{ $offset }}"
                    stroke-linecap="{{ $rounded ? 'round' : 'butt' }}"
                    @if($size === 'half') transform="rotate(180, 50, 50)" @endif
                    class="transition-all duration-500"
                />
            </svg>
            <div class="text-2xl font-bold mt-2">
                {{ number_format($this->getValue(), (int) $this->config('decimal_precision', 0)) }}
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
