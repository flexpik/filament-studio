<x-filament-panels::page>
    @push('styles')
        <meta name="studio-api-key" content="{{ $this->canvasApiKey }}">
        @vite(['resources/css/app.css', 'packages/flexpik/filament-studio/resources/js/flows/flow-canvas.tsx'])
    @endpush

    <div
        id="flow-canvas-root"
        data-flow-id="{{ $record->id }}"
        data-api-base="{{ $this->getApiBase() }}"
        data-flow-name="{{ $record->name }}"
        class="h-[calc(100dvh-10rem)] w-full"
    ></div>
</x-filament-panels::page>
