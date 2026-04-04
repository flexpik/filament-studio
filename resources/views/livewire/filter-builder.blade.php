<style>
    /*
     * Advanced Filter Builder styles.
     *
     * Uses the same OKLCH token pattern as Version History.
     */

    /* ── Light theme tokens ── */
    .fb {
        --fb-bg: #fff;
        --fb-border: var(--gray-200);
        --fb-text: var(--gray-950);
        --fb-text-secondary: var(--gray-500);
        --fb-text-muted: var(--gray-400);

        --fb-input-bg: #fff;
        --fb-input-border: var(--gray-300);
        --fb-input-text: var(--gray-900);
        --fb-input-placeholder: var(--gray-400);

        --fb-logic-bg: var(--primary-50);
        --fb-logic-text: var(--primary-700);

        --fb-group-border: var(--gray-200);
        --fb-group-accent: var(--primary-500);

        --fb-btn-bg: #fff;
        --fb-btn-border: var(--gray-300);
        --fb-btn-text: var(--gray-700);
        --fb-btn-hover-bg: var(--gray-50);

        --fb-btn-primary-bg: var(--primary-600);
        --fb-btn-primary-text: #fff;
        --fb-btn-primary-hover-bg: var(--primary-500);

        --fb-btn-danger-text: var(--danger-600);
        --fb-btn-danger-hover-text: var(--danger-500);

        --fb-link-text: var(--primary-600);

        --fb-remove-text: var(--danger-500);
        --fb-remove-hover-text: var(--danger-700);
    }

    /* ── Dark theme tokens ── */
    .dark .fb {
        --fb-bg: var(--gray-900);
        --fb-border: var(--gray-700);
        --fb-text: var(--gray-100);
        --fb-text-secondary: var(--gray-400);
        --fb-text-muted: var(--gray-500);

        --fb-input-bg: var(--gray-800);
        --fb-input-border: var(--gray-600);
        --fb-input-text: var(--gray-100);
        --fb-input-placeholder: var(--gray-500);

        --fb-logic-bg: var(--primary-950);
        --fb-logic-text: var(--primary-300);

        --fb-group-border: var(--gray-700);
        --fb-group-accent: var(--primary-400);

        --fb-btn-bg: var(--gray-800);
        --fb-btn-border: var(--gray-600);
        --fb-btn-text: var(--gray-200);
        --fb-btn-hover-bg: var(--gray-700);

        --fb-btn-primary-bg: var(--primary-600);
        --fb-btn-primary-text: #fff;
        --fb-btn-primary-hover-bg: var(--primary-500);

        --fb-btn-danger-text: var(--danger-400);
        --fb-btn-danger-hover-text: var(--danger-300);

        --fb-link-text: var(--primary-400);

        --fb-remove-text: var(--danger-400);
        --fb-remove-hover-text: var(--danger-300);
    }

    /* ── Layout ── */
    .fb__container { display: flex; flex-direction: column; gap: 0.75rem; }

    /* ── Group ── */
    .fb__group {
        border-radius: 0.5rem;
        border: 1px solid var(--fb-group-border);
        padding: 0.75rem;
        background-color: var(--fb-bg);
    }

    .fb__group--nested {
        margin-left: 1rem;
        border-left: 3px solid var(--fb-group-accent);
    }

    .fb__group-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }

    /* ── Logic toggle ── */
    .fb__logic-btn {
        border-radius: 0.375rem;
        background-color: var(--fb-logic-bg);
        padding: 0.25rem 0.5rem;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--fb-logic-text);
        border: none;
        cursor: pointer;
        transition: opacity 0.15s;
    }

    .fb__logic-btn:hover { opacity: 0.8; }

    .fb__logic-hint {
        font-size: 0.6875rem;
        color: var(--fb-text-muted);
    }

    /* ── Rules list ── */
    .fb__rules { display: flex; flex-direction: column; gap: 0.5rem; }

    /* ── Rule row ── */
    .fb__rule {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* ── Form controls ── */
    .fb__select,
    .fb__input {
        min-width: 140px;
        border-radius: 0.5rem;
        border: 1px solid var(--fb-input-border);
        background-color: var(--fb-input-bg);
        padding: 0.375rem 0.5rem;
        font-size: 0.8125rem;
        color: var(--fb-input-text);
        outline: none;
        transition: border-color 0.15s;
    }

    .fb__select:focus,
    .fb__input:focus {
        border-color: var(--fb-group-accent);
    }

    .fb__select::placeholder,
    .fb__input::placeholder {
        color: var(--fb-input-placeholder);
    }

    /* ── Remove button ── */
    .fb__remove-btn {
        display: inline-flex;
        background: none;
        border: none;
        cursor: pointer;
        color: var(--fb-remove-text);
        padding: 0.125rem;
        transition: color 0.15s;
    }

    .fb__remove-btn:hover { color: var(--fb-remove-hover-text); }

    /* ── Subgroup links ── */
    .fb__subgroup-links {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    .fb__subgroup-link {
        font-size: 0.6875rem;
        color: var(--fb-link-text);
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        text-decoration: none;
    }

    .fb__subgroup-link:hover { text-decoration: underline; }

    /* ── Action bar ── */
    .fb__actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-top: 0.5rem;
    }

    .fb__actions-right {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* ── Buttons ── */
    .fb__btn {
        border-radius: 0.5rem;
        padding: 0.375rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.15s, border-color 0.15s, color 0.15s;
    }

    .fb__btn--secondary {
        background-color: var(--fb-btn-bg);
        border: 1px solid var(--fb-btn-border);
        color: var(--fb-btn-text);
    }

    .fb__btn--secondary:hover { background-color: var(--fb-btn-hover-bg); }

    .fb__btn--primary {
        background-color: var(--fb-btn-primary-bg);
        border: 1px solid transparent;
        color: var(--fb-btn-primary-text);
    }

    .fb__btn--primary:hover { background-color: var(--fb-btn-primary-hover-bg); }

    .fb__btn--danger {
        background: none;
        border: none;
        color: var(--fb-btn-danger-text);
        padding: 0.375rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 500;
        cursor: pointer;
    }

    .fb__btn--danger:hover { color: var(--fb-btn-danger-hover-text); }
</style>

<div class="fb">
    <div class="fb__container">
        @include('filament-studio::livewire.partials.filter-group', [
            'group' => $tree,
            'path' => '',
            'fieldOptions' => $fieldOptions,
            'depth' => 0,
        ])

        <div class="fb__actions">
            <button type="button" wire:click="addRule" class="fb__btn fb__btn--secondary">
                Add Rule
            </button>

            <button type="button" wire:click="addGroup" class="fb__btn fb__btn--secondary">
                Add Group
            </button>

            <div class="fb__actions-right">
                <button type="button" wire:click="clearFilter" class="fb__btn fb__btn--danger">
                    Clear
                </button>

                <button type="button" wire:click="applyFilter" class="fb__btn fb__btn--primary">
                    Apply Filter
                </button>
            </div>
        </div>
    </div>
</div>
