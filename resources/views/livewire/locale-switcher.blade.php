<div>
    @if($visible)
    <div class="flex items-center gap-1">
        @foreach($locales as $locale)
            <button
                wire:click="switchLocale('{{ $locale }}')"
                type="button"
                @class([
                    'px-2 py-1 text-xs font-medium rounded-md transition',
                    'bg-primary-500 text-white' => $activeLocale === $locale,
                    'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' => $activeLocale !== $locale,
                ])
            >
                {{ strtoupper($locale) }}
            </button>
        @endforeach
    </div>
    @endif
</div>
