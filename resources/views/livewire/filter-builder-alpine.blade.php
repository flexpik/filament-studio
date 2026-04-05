@php
    $unaryOperators = ['is_null', 'is_not_null', 'is_empty', 'is_not_empty', 'is_true', 'is_false'];
@endphp

<style>
    .fb {
        --fb-bg: #fff; --fb-text-muted: var(--gray-400);
        --fb-input-bg: #fff; --fb-input-border: var(--gray-300); --fb-input-text: var(--gray-900);
        --fb-logic-bg: var(--primary-50); --fb-logic-text: var(--primary-700);
        --fb-group-border: var(--gray-200); --fb-group-accent: var(--primary-500);
        --fb-btn-bg: #fff; --fb-btn-border: var(--gray-300); --fb-btn-text: var(--gray-700); --fb-btn-hover-bg: var(--gray-50);
        --fb-btn-primary-bg: var(--primary-600); --fb-btn-primary-text: #fff; --fb-btn-primary-hover-bg: var(--primary-500);
        --fb-btn-danger-text: var(--danger-600); --fb-btn-danger-hover-text: var(--danger-500);
        --fb-link-text: var(--primary-600);
        --fb-remove-text: var(--danger-500); --fb-remove-hover-text: var(--danger-700);
    }
    .dark .fb {
        --fb-bg: var(--gray-900); --fb-text-muted: var(--gray-500);
        --fb-input-bg: var(--gray-800); --fb-input-border: var(--gray-600); --fb-input-text: var(--gray-100);
        --fb-logic-bg: var(--primary-950); --fb-logic-text: var(--primary-300);
        --fb-group-border: var(--gray-700); --fb-group-accent: var(--primary-400);
        --fb-btn-bg: var(--gray-800); --fb-btn-border: var(--gray-600); --fb-btn-text: var(--gray-200); --fb-btn-hover-bg: var(--gray-700);
        --fb-btn-primary-bg: var(--primary-600); --fb-btn-primary-text: #fff; --fb-btn-primary-hover-bg: var(--primary-500);
        --fb-btn-danger-text: var(--danger-400); --fb-btn-danger-hover-text: var(--danger-300);
        --fb-link-text: var(--primary-400);
        --fb-remove-text: var(--danger-400); --fb-remove-hover-text: var(--danger-300);
    }
    .fb__group { border-radius: 0.5rem; border: 1px solid var(--fb-group-border); padding: 0.75rem; background: var(--fb-bg); }
    .fb__group--nested { margin-left: 1rem; border-left: 3px solid var(--fb-group-accent); }
    .fb__group-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
    .fb__logic-btn { border-radius: 0.375rem; background: var(--fb-logic-bg); padding: 0.25rem 0.5rem; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--fb-logic-text); border: none; cursor: pointer; }
    .fb__logic-hint { font-size: 0.6875rem; color: var(--fb-text-muted); }
    .fb__rules { display: flex; flex-direction: column; gap: 0.5rem; }
    .fb__rule { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
    .fb__select, .fb__input { min-width: 140px; border-radius: 0.5rem; border: 1px solid var(--fb-input-border); background: var(--fb-input-bg); padding: 0.375rem 0.5rem; font-size: 0.8125rem; color: var(--fb-input-text); outline: none; }
    .fb__select:focus, .fb__input:focus { border-color: var(--fb-group-accent); }
    .fb__remove-btn { display: inline-flex; background: none; border: none; cursor: pointer; color: var(--fb-remove-text); padding: 0.125rem; }
    .fb__remove-btn:hover { color: var(--fb-remove-hover-text); }
    .fb__subgroup-links { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
    .fb__subgroup-link { font-size: 0.6875rem; color: var(--fb-link-text); background: none; border: none; cursor: pointer; padding: 0; }
    .fb__subgroup-link:hover { text-decoration: underline; }
    .fb__actions { display: flex; align-items: center; gap: 0.5rem; padding-top: 0.75rem; }
    .fb__actions-right { margin-left: auto; display: flex; gap: 0.5rem; }
    .fb__btn { border-radius: 0.5rem; padding: 0.375rem 0.75rem; font-size: 0.8125rem; font-weight: 500; cursor: pointer; }
    .fb__btn--secondary { background: var(--fb-btn-bg); border: 1px solid var(--fb-btn-border); color: var(--fb-btn-text); }
    .fb__btn--secondary:hover { background: var(--fb-btn-hover-bg); }
    .fb__btn--primary { background: var(--fb-btn-primary-bg); border: 1px solid transparent; color: var(--fb-btn-primary-text); }
    .fb__btn--primary:hover { background: var(--fb-btn-primary-hover-bg); }
    .fb__btn--danger { background: none; border: none; color: var(--fb-btn-danger-text); font-size: 0.8125rem; font-weight: 500; cursor: pointer; }
    .fb__btn--danger:hover { color: var(--fb-btn-danger-hover-text); }
</style>

<div
    class="fb"
    style="padding: 1rem;"
    x-data="{
        tree: @js($tree),
        fieldOptions: @js($fieldOptions),
        operatorsByField: @js($operatorsByField),
        unaryOps: @js($unaryOperators),

        addRule(group) {
            group.rules.push({ field: '', operator: 'eq', value: '' });
        },
        addGroup(group) {
            group.rules.push({ logic: 'and', rules: [] });
        },
        removeItem(group, index) {
            group.rules.splice(index, 1);
        },
        toggleLogic(group) {
            group.logic = group.logic === 'and' ? 'or' : 'and';
        },
        isGroup(item) {
            return item.hasOwnProperty('logic');
        },
        isUnary(op) {
            return this.unaryOps.includes(op);
        },
        getOperators(fieldName) {
            return this.operatorsByField[fieldName] || {};
        },
        apply() {
            this.$wire.applyFilterTree(JSON.parse(JSON.stringify(this.tree))).then(() => {
                this.$wire.unmountAction();
            });
        },
        clear() {
            this.tree = { logic: 'and', rules: [] };
            this.$wire.applyFilterTree(JSON.parse(JSON.stringify(this.tree))).then(() => {
                this.$wire.unmountAction();
            });
        }
    }"
>
    <div style="display:flex;flex-direction:column;gap:0.75rem;">
        {{-- Recursive group template --}}
        <template x-ref="groupTpl"></template>

        {{-- Root group --}}
        <div x-data="{ group: tree, depth: 0 }">
            <template x-if="true">
                <div :class="'fb__group' + (depth > 0 ? ' fb__group--nested' : '')">
                    <div class="fb__group-header">
                        <button type="button" class="fb__logic-btn" x-on:click="toggleLogic(group)" x-text="group.logic === 'and' ? 'AND' : 'OR'"></button>
                        <span class="fb__logic-hint" x-text="group.logic === 'and' ? 'All conditions must match' : 'Any condition must match'"></span>
                    </div>

                    <div class="fb__rules">
                        <template x-for="(item, idx) in group.rules" :key="idx">
                            <div>
                                {{-- Nested group --}}
                                <template x-if="isGroup(item)">
                                    <div>
                                        <div class="fb__group fb__group--nested">
                                            <div class="fb__group-header">
                                                <button type="button" class="fb__logic-btn" x-on:click="toggleLogic(item)" x-text="item.logic === 'and' ? 'AND' : 'OR'"></button>
                                                <span class="fb__logic-hint" x-text="item.logic === 'and' ? 'All conditions must match' : 'Any condition must match'"></span>
                                            </div>
                                            <div class="fb__rules">
                                                <template x-for="(subItem, subIdx) in item.rules" :key="subIdx">
                                                    <div class="fb__rule">
                                                        <select class="fb__select" x-model="subItem.field">
                                                            <option value="">Select field...</option>
                                                            <template x-for="[val, label] in Object.entries(fieldOptions)" :key="val">
                                                                <option :value="val" x-text="label"></option>
                                                            </template>
                                                        </select>
                                                        <template x-if="subItem.field">
                                                            <select class="fb__select" x-model="subItem.operator">
                                                                <template x-for="[val, label] in Object.entries(getOperators(subItem.field))" :key="val">
                                                                    <option :value="val" x-text="label"></option>
                                                                </template>
                                                            </select>
                                                        </template>
                                                        <template x-if="subItem.field && subItem.operator && !isUnary(subItem.operator)">
                                                            <input type="text" class="fb__input" x-model="subItem.value" placeholder="Value..." />
                                                        </template>
                                                        <button type="button" class="fb__remove-btn" x-on:click="removeItem(item, subIdx)" title="Remove">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="fb__subgroup-links">
                                                <button type="button" class="fb__subgroup-link" x-on:click="addRule(item)">+ Rule</button>
                                                <button type="button" class="fb__remove-btn" x-on:click="removeItem(group, idx)" title="Remove group" style="margin-left:auto;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                {{-- Rule row --}}
                                <template x-if="!isGroup(item)">
                                    <div class="fb__rule">
                                        <select class="fb__select" x-model="item.field">
                                            <option value="">Select field...</option>
                                            <template x-for="[val, label] in Object.entries(fieldOptions)" :key="val">
                                                <option :value="val" x-text="label"></option>
                                            </template>
                                        </select>
                                        <template x-if="item.field">
                                            <select class="fb__select" x-model="item.operator">
                                                <template x-for="[val, label] in Object.entries(getOperators(item.field))" :key="val">
                                                    <option :value="val" x-text="label"></option>
                                                </template>
                                            </select>
                                        </template>
                                        <template x-if="item.field && item.operator && !isUnary(item.operator)">
                                            <input type="text" class="fb__input" x-model="item.value" placeholder="Value..." />
                                        </template>
                                        <button type="button" class="fb__remove-btn" x-on:click="removeItem(group, idx)" title="Remove">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <div class="fb__actions">
            <button type="button" class="fb__btn fb__btn--secondary" x-on:click="addRule(tree)">Add Rule</button>
            <button type="button" class="fb__btn fb__btn--secondary" x-on:click="addGroup(tree)">Add Group</button>
            <div class="fb__actions-right">
                <button type="button" class="fb__btn fb__btn--danger" x-on:click="clear()">Clear</button>
                <button type="button" class="fb__btn fb__btn--primary" x-on:click="apply()">Apply Filter</button>
            </div>
        </div>
    </div>
</div>
