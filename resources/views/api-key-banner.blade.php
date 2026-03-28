<div class="mt-4 w-full rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" x-data="{ copied: false }">
    <div class="flex items-center gap-x-3 px-6 py-4">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-green-50 dark:bg-green-500/10">
            <x-heroicon-o-key class="size-6 text-green-600 dark:text-green-400" />
        </div>
        <div class="grid flex-1 gap-y-1">
            <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Your New API Key
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Copy it now — it will not be shown again.
            </p>
        </div>
    </div>
    <div class="border-t border-gray-200 px-6 py-4 dark:border-white/10">
        <div class="flex items-center gap-x-3">
            <input
                type="text"
                value="{{ $plainKey }}"
                readonly
                x-ref="apiKey"
                @click="$refs.apiKey.select()"
                class="block w-full rounded-lg border-none bg-gray-50 px-3 py-2 font-mono text-sm text-gray-950 ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:focus:ring-primary-500"
            />
            <button
                type="button"
                x-on:click="navigator.clipboard.writeText($refs.apiKey.value); copied = true; setTimeout(() => copied = false, 2000)"
                class="inline-flex shrink-0 items-center gap-x-1.5 rounded-lg bg-white px-3 py-2 text-sm font-semibold shadow-sm ring-1 ring-gray-950/10 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-200 dark:ring-white/20 dark:hover:bg-white/10"
                :class="copied ? 'text-green-600 dark:text-green-400' : 'text-gray-700'"
            >
                <x-heroicon-o-clipboard x-show="!copied" class="size-5" />
                <x-heroicon-o-check x-show="copied" x-cloak class="size-5" />
                <span x-text="copied ? 'Copied!' : 'Copy'"></span>
            </button>
        </div>
    </div>
</div>
