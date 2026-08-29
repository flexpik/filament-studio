<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Run metadata --}}
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="font-semibold">Flow:</span> {{ $record->name }}</div>
            <div><span class="font-semibold">Version:</span> {{ $run->flow_version_id ?? 'draft' }}</div>
            <div><span class="font-semibold">Status:</span> {{ $run->status?->value }}</div>
            <div><span class="font-semibold">Trigger:</span> {{ $run->trigger_type }}</div>
            <div><span class="font-semibold">Started:</span> {{ $run->started_at?->diffForHumans() }}</div>
            <div><span class="font-semibold">Duration:</span> {{ $run->duration_ms ?? '—' }}ms</div>
        </div>

        {{-- Step timeline grouped by operation_key --}}
        <div class="border rounded-md divide-y">
            @foreach ($this->groupedSteps as $operationKey => $attempts)
                @php $multipleAttempts = $attempts->count() > 1; @endphp
                @foreach ($attempts->sortBy('attempt_number') as $step)
                    <div class="p-3" data-status="{{ $step->status?->value }}">
                        <div class="flex items-center justify-between">
                            <div class="font-mono text-sm">
                                {{ $operationKey }} ({{ $step->operation_type }})
                                @if ($multipleAttempts)
                                    <span class="ml-2 text-xs text-gray-500">Attempt {{ $step->attempt_number }}</span>
                                @endif
                            </div>
                            <div class="text-xs">
                                {{ $step->status?->value }}
                                @if ($step->started_at && $step->finished_at)
                                    — {{ $step->finished_at->diffInMilliseconds($step->started_at) }}ms
                                @endif
                            </div>
                        </div>

                        {{-- Error panel --}}
                        @if ($step->error_class || $step->error_message)
                            <div class="mt-2 text-sm text-red-600">
                                @if ($step->error_class)
                                    <span class="font-semibold">{{ $step->error_class }}</span>:
                                @endif
                                {{ $step->error_message }}
                            </div>
                            @if ($step->error_trace && $this->isAdminUser())
                                <details class="mt-1">
                                    <summary class="cursor-pointer text-xs text-red-400">Stack trace</summary>
                                    <pre class="mt-1 overflow-auto p-2 bg-red-50 dark:bg-red-950 text-xs text-red-700 dark:text-red-300">{{ $step->error_trace }}</pre>
                                </details>
                            @endif
                        @endif

                        {{-- Masked I/O --}}
                        <details class="mt-2 text-xs">
                            <summary class="cursor-pointer">Input/Output</summary>
                            <pre class="overflow-auto p-2 bg-gray-50 dark:bg-gray-900">{{ json_encode(['input' => $step->masked_input, 'output' => $step->masked_output], JSON_PRETTY_PRINT) }}</pre>
                        </details>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
