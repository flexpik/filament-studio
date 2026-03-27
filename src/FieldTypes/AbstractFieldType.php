<?php

namespace Flexpik\FilamentStudio\FieldTypes;

use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Enums\FieldWidth;
use Flexpik\FilamentStudio\Models\StudioField;

abstract class AbstractFieldType
{
    /** Unique key matching dm_fields.field_type */
    public static string $key;

    /** Human-readable label for the field type picker */
    public static string $label;

    /** Heroicon name for the field type picker */
    public static string $icon;

    /** Which val_* column this type stores data in */
    public static EavCast $eavCast;

    /** Category grouping: text, numeric, boolean, selection, datetime, file, relational, structured, presentation */
    public static string $category;

    public function __construct(
        public readonly StudioField $field,
    ) {}

    /**
     * Filament form schema for type-specific settings (used in the field editor).
     *
     * @return array<Component>
     */
    abstract public static function settingsSchema(): array;

    /**
     * Generate the Filament form component for data entry.
     */
    abstract public function toFilamentComponent(): Component;

    /**
     * Generate the Filament table column for listing. Return null to hide from tables.
     */
    abstract public function toTableColumn(): ?Column;

    /**
     * Generate the Filament table filter. Return null if not filterable.
     */
    abstract public function toFilter(): ?BaseFilter;

    /**
     * Build a fully configured form component with common properties applied.
     */
    public function buildFormComponent(?string $pageContext = null): Component
    {
        $component = $this->toFilamentComponent();

        return $this->applyCommonProperties($component, $pageContext);
    }

    /**
     * Build a fully configured table column with common properties applied.
     */
    public function buildTableColumn(): ?Column
    {
        $column = $this->toTableColumn();

        if ($column === null) {
            return null;
        }

        return $this->applyCommonColumnProperties($column);
    }

    /**
     * Retrieve a value from the field's settings JSON.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        $settings = $this->field->settings ?? [];

        return $settings[$key] ?? $default;
    }

    /**
     * Apply common form component properties shared by all field types.
     */
    protected function applyCommonProperties(Component $component, ?string $pageContext = null): Component
    {
        if (method_exists($component, 'required') && $this->field->is_required) {
            $component->required();
        }

        if (method_exists($component, 'placeholder') && $this->field->placeholder) {
            $component->placeholder($this->field->placeholder);
        }

        if (method_exists($component, 'hint') && $this->field->hint) {
            $component->hint($this->field->hint);
        }

        $isDisabled = match ($pageContext) {
            'create' => $this->field->is_disabled_on_create ?? false,
            'edit' => $this->field->is_disabled_on_edit ?? false,
            default => false,
        };

        if ($isDisabled) {
            $component->disabled();
        }

        if (method_exists($component, 'label') && $this->field->label) {
            $component->label($this->field->label);
        }

        $this->applyColumnSpan($component);
        $this->applyValidationRules($component);

        return $component;
    }

    /**
     * Apply column span based on field width setting.
     */
    protected function applyColumnSpan(Component $component): Component
    {
        $width = $this->field->width;

        $span = match ($width) {
            FieldWidth::Half => 1,
            FieldWidth::Full => 2,
            FieldWidth::Expanded => 'full',
        };
        $component->columnSpan($span);

        return $component;
    }

    /**
     * Apply custom validation rules from the field's validation_rules JSON.
     */
    protected function applyValidationRules(Component $component): Component
    {
        if (! method_exists($component, 'rules')) {
            return $component;
        }

        $rules = $this->field->validation_rules ?? [];

        if (! empty($rules)) {
            $component->rules($rules);
        }

        return $component;
    }

    /**
     * Apply common table column properties.
     */
    protected function applyCommonColumnProperties(Column $column): Column
    {
        if ($this->field->label) {
            $column->label($this->field->label);
        }

        $column->sortable()->searchable();
        $column->toggleable();

        return $column;
    }
}
