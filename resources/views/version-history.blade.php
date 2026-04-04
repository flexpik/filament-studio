@php
    $fieldLabels = $fieldLabels ?? [];
    $fieldTypes = $fieldTypes ?? [];
    $sensitiveFields = $sensitiveFields ?? [];
    $showRestore = $showRestore ?? false;

    // Build version number map: oldest = v1, newest = vN
    $total = $versions->count();
    $versionNumbers = [];
    foreach ($versions as $index => $version) {
        $versionNumbers[$version->id] = $total - $index;
    }

    // Build previous-snapshot map for diff (versions are newest-first)
    $previousSnapshots = [];
    $versionList = $versions->values();
    foreach ($versionList as $index => $version) {
        $previousSnapshots[$version->id] = $versionList[$index + 1]?->snapshot ?? null;
    }

    // Type-aware value formatter
    $formatValue = function ($val, string $fieldName) use ($fieldTypes) {
        if ($val === null || $val === '') {
            return '—';
        }

        $type = $fieldTypes[$fieldName] ?? 'text';

        return match ($type) {
            'boolean' => $val ? 'Yes' : 'No',
            'datetime' => (function () use ($val) {
                try {
                    return \Carbon\Carbon::parse($val)->format('M j, Y H:i');
                } catch (\Throwable) {
                    return $val;
                }
            })(),
            'json' => is_array($val) ? json_encode($val, JSON_PRETTY_PRINT) : $val,
            'integer' => is_numeric($val) ? number_format((int) $val) : $val,
            'decimal' => is_numeric($val) ? rtrim(rtrim(number_format((float) $val, 4), '0'), '.') : $val,
            default => is_array($val) ? json_encode($val) : (string) $val,
        };
    };
@endphp

<style>
    /*
     * Version History styles.
     *
     * Filament v5 injects OKLCH values into --primary-*, --gray-*, etc.
     * Use var(--name) directly — no rgb() wrapper needed.
     */

    /* ── Light theme tokens ── */
    .vh {
        --vh-bg-card: #fff;
        --vh-border: var(--gray-200);
        --vh-border-active: var(--primary-300);
        --vh-bg-footer: var(--gray-50);
        --vh-border-footer: var(--gray-100);

        --vh-text: var(--gray-950);
        --vh-text-secondary: var(--gray-500);
        --vh-text-muted: var(--gray-400);

        --vh-badge-bg: var(--gray-400);
        --vh-badge-active-bg: var(--primary-500);

        --vh-tag-bg: var(--warning-50);
        --vh-tag-text: var(--warning-600);

        --vh-avatar-bg: var(--primary-500);

        --vh-diff-border: var(--success-400);
        --vh-diff-bg: var(--success-50);
        --vh-diff-old: var(--danger-400);
        --vh-diff-new: var(--success-600);

        --vh-btn-border: var(--gray-300);
        --vh-btn-text: var(--gray-500);
        --vh-btn-hover-border: var(--gray-400);
        --vh-btn-hover-text: var(--gray-700);

        --vh-connector: var(--gray-200);
        --vh-empty-border: var(--gray-300);
    }

    /* ── Dark theme tokens ── */
    .dark .vh {
        --vh-bg-card: var(--gray-900);
        --vh-border: var(--gray-700);
        --vh-border-active: var(--primary-600);
        --vh-bg-footer: var(--gray-950);
        --vh-border-footer: var(--gray-700);

        --vh-text: var(--gray-100);
        --vh-text-secondary: var(--gray-400);
        --vh-text-muted: var(--gray-500);

        --vh-badge-bg: var(--gray-500);
        --vh-badge-active-bg: var(--primary-600);

        --vh-tag-bg: var(--warning-950);
        --vh-tag-text: var(--warning-400);

        --vh-avatar-bg: var(--primary-600);

        --vh-diff-border: var(--success-500);
        --vh-diff-bg: var(--success-950);
        --vh-diff-old: var(--danger-400);
        --vh-diff-new: var(--success-400);

        --vh-btn-border: var(--gray-600);
        --vh-btn-text: var(--gray-400);
        --vh-btn-hover-border: var(--gray-500);
        --vh-btn-hover-text: var(--gray-300);

        --vh-connector: var(--gray-700);
        --vh-empty-border: var(--gray-600);
    }

    /* ── Layout ── */
    .vh__list {
        display: flex;
        flex-direction: column;
        gap: 0.375rem;
        padding: 1rem;
    }

    /* ── Card ── */
    .vh__card {
        overflow: hidden;
        border-radius: 0.5rem;
        border: 1px solid var(--vh-border);
        background-color: var(--vh-bg-card);
    }

    .vh__card--latest {
        border-color: var(--vh-border-active);
    }

    /* ── Header ── */
    .vh__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.625rem 0.75rem;
    }

    .vh__header-left,
    .vh__header-right {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vh__header-right {
        gap: 0.375rem;
    }

    /* ── Version badge ── */
    .vh__badge {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        padding: 0.125rem 0.5rem;
        font-size: 10px;
        font-weight: 700;
        color: #fff;
        background-color: var(--vh-badge-bg);
    }

    .vh__badge--latest {
        background-color: var(--vh-badge-active-bg);
    }

    /* ── Timestamp ── */
    .vh__time {
        font-size: 0.75rem;
        color: var(--vh-text-secondary);
    }

    /* ── Latest tag ── */
    .vh__tag {
        border-radius: 9999px;
        padding: 0.125rem 0.375rem;
        font-size: 9px;
        font-weight: 600;
        background-color: var(--vh-tag-bg);
        color: var(--vh-tag-text);
    }

    /* ── User avatar ── */
    .vh__avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 9999px;
        background-color: var(--vh-avatar-bg);
        font-size: 10px;
        font-weight: 600;
        color: #fff;
    }

    .vh__user {
        font-size: 11px;
        color: var(--vh-text-secondary);
    }

    /* ── Changes summary ── */
    .vh__summary {
        padding: 0 0.75rem 0.375rem;
        font-size: 11px;
        color: var(--vh-text-muted);
    }

    /* ── Snapshot fields ── */
    .vh__fields {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
        padding: 0 0.75rem 0.75rem;
    }

    .vh__field {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
        border-radius: 0.25rem;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .vh__field--changed {
        border-left: 3px solid var(--vh-diff-border);
        background-color: var(--vh-diff-bg);
    }

    .vh__field-label {
        min-width: 60px;
        flex-shrink: 0;
        color: var(--vh-text-secondary);
    }

    .vh__old-value {
        color: var(--vh-diff-old);
        text-decoration: line-through;
    }

    .vh__arrow {
        margin: 0 0.25rem;
        color: var(--vh-text-muted);
    }

    .vh__new-value {
        color: var(--vh-diff-new);
    }

    .vh__unchanged-value {
        color: var(--vh-text-muted);
    }

    /* ── Footer ── */
    .vh__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-top: 1px solid var(--vh-border-footer);
        background-color: var(--vh-bg-footer);
        padding: 0.5rem 0.75rem;
    }

    .vh__footer-date {
        font-size: 11px;
        color: var(--vh-text-muted);
    }

    /* ── Restore button ── */
    .vh__restore-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        border-radius: 0.375rem;
        border: 1px solid var(--vh-btn-border);
        padding: 0.25rem 0.5rem;
        font-size: 11px;
        color: var(--vh-btn-text);
        background: none;
        cursor: pointer;
        transition: border-color 0.15s, color 0.15s;
    }

    .vh__restore-btn:hover {
        border-color: var(--vh-btn-hover-border);
        color: var(--vh-btn-hover-text);
    }

    /* ── Connector ── */
    .vh__connector {
        display: flex;
        justify-content: center;
    }

    .vh__connector-line {
        height: 0.375rem;
        width: 1px;
        background-color: var(--vh-connector);
    }

    /* ── Empty state ── */
    .vh__empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        border: 1px dashed var(--vh-empty-border);
        padding: 3rem 0;
    }

    .vh__empty-icon {
        margin-bottom: 0.75rem;
        width: 2rem;
        height: 2rem;
        color: var(--vh-text-muted);
    }

    .vh__empty-title {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--vh-text-secondary);
    }

    .vh__empty-desc {
        margin-top: 0.25rem;
        font-size: 0.75rem;
        color: var(--vh-text-muted);
    }
</style>

<div class="vh">
    <div class="vh__list">
        @forelse ($versions as $version)
            @php
                $versionNumber = $versionNumbers[$version->id];
                $isLatest = $loop->first;
                $previousSnapshot = $previousSnapshots[$version->id];
                $snapshot = $version->snapshot;
                $totalFields = count($snapshot);

                // Count changed fields (exclude sensitive)
                $changedCount = 0;
                $visibleFieldCount = 0;
                if ($previousSnapshot !== null) {
                    foreach ($snapshot as $field => $value) {
                        if (in_array($field, $sensitiveFields)) {
                            continue;
                        }
                        $visibleFieldCount++;
                        $oldValue = $previousSnapshot[$field] ?? null;
                        if ($value !== $oldValue) {
                            $changedCount++;
                        }
                    }
                } else {
                    $visibleFieldCount = count(array_diff_key($snapshot, array_flip($sensitiveFields)));
                }

                // User info
                $userName = $version->creator?->name ?? 'System';
                $userInitial = mb_strtoupper(mb_substr($userName, 0, 1));
            @endphp

            <div class="vh__card {{ $isLatest ? 'vh__card--latest' : '' }}">
                <div class="vh__header">
                    <div class="vh__header-left">
                        <span class="vh__badge {{ $isLatest ? 'vh__badge--latest' : '' }}">
                            v{{ $versionNumber }}
                        </span>
                        <span class="vh__time">
                            {{ $version->created_at->diffForHumans() }}
                        </span>
                        @if ($isLatest)
                            <span class="vh__tag">LATEST</span>
                        @endif
                    </div>
                    <div class="vh__header-right">
                        <div class="vh__avatar">{{ $userInitial }}</div>
                        <span class="vh__user">{{ $userName }}</span>
                    </div>
                </div>

                @if ($previousSnapshot !== null)
                    <div class="vh__summary">
                        {{ $changedCount }} of {{ $visibleFieldCount }} {{ Str::plural('field', $visibleFieldCount) }} changed
                    </div>
                @endif

                <div class="vh__fields">
                    @foreach ($snapshot as $field => $value)
                        @if (in_array($field, $sensitiveFields))
                            @continue
                        @endif

                        @php
                            $label = $fieldLabels[$field] ?? Str::headline($field);
                            $displayValue = $formatValue($value, $field);
                            $isChanged = $previousSnapshot !== null && ($value !== ($previousSnapshot[$field] ?? null));
                            $oldValue = $previousSnapshot[$field] ?? null;
                            $oldDisplayValue = $formatValue($oldValue, $field);
                        @endphp

                        @if ($isChanged)
                            <div class="vh__field vh__field--changed">
                                <span class="vh__field-label">{{ $label }}</span>
                                <span>
                                    <span class="vh__old-value">{{ $oldDisplayValue }}</span>
                                    <span class="vh__arrow">&rarr;</span>
                                    <span class="vh__new-value">{{ $displayValue }}</span>
                                </span>
                            </div>
                        @else
                            <div class="vh__field">
                                <span class="vh__field-label">{{ $label }}</span>
                                <span class="vh__unchanged-value">{{ $displayValue }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="vh__footer">
                    <span class="vh__footer-date">
                        {{ $version->created_at->format('M j, Y \\a\\t H:i') }}
                    </span>
                    @if ($showRestore && ! $isLatest)
                        <button
                            type="button"
                            x-on:click="$wire.mountAction('restoreVersion', { versionId: {{ $version->id }} })"
                            class="vh__restore-btn"
                        >
                            &#8617; Restore
                        </button>
                    @endif
                </div>
            </div>

            @if (! $loop->last)
                <div class="vh__connector">
                    <div class="vh__connector-line"></div>
                </div>
            @endif
        @empty
            <div class="vh__empty">
                <x-heroicon-o-clock class="vh__empty-icon" />
                <p class="vh__empty-title">No version history yet</p>
                <p class="vh__empty-desc">Changes will be tracked automatically when this record is updated.</p>
            </div>
        @endforelse
    </div>
</div>
