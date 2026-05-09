<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Support;

use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioFieldOption;

class McpSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function collection(StudioCollection $c): array
    {
        return [
            'id' => $c->id,
            'slug' => $c->slug,
            'name' => $c->name,
            'label' => $c->label,
            'label_plural' => $c->label_plural,
            'description' => $c->description,
            'icon' => $c->icon,
            'is_singleton' => (bool) $c->is_singleton,
            'is_hidden' => (bool) $c->is_hidden,
            'api_enabled' => (bool) $c->api_enabled,
            'sort_field' => $c->sort_field,
            'sort_direction' => $c->sort_direction?->value,
            'enable_versioning' => (bool) $c->enable_versioning,
            'enable_soft_deletes' => (bool) $c->enable_soft_deletes,
            'archive_field' => $c->archive_field,
            'archive_value' => $c->archive_value,
            'display_template' => $c->display_template,
            'supported_locales' => $c->supported_locales,
            'default_locale' => $c->default_locale,
            'settings' => $c->settings,
            'fields' => $c->relationLoaded('fields')
                ? $c->fields->map(fn ($f) => $this->field($f))->all()
                : null,
            'created_at' => optional($c->created_at)->toIso8601String(),
            'updated_at' => optional($c->updated_at)->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function field(StudioField $f): array
    {
        return [
            'id' => $f->id,
            'column_name' => $f->column_name,
            'label' => $f->label,
            'field_type' => $f->field_type,
            'eav_cast' => $f->eav_cast instanceof EavCast
                ? $f->eav_cast->value
                : $f->eav_cast,
            'is_required' => (bool) $f->is_required,
            'is_unique' => (bool) $f->is_unique,
            'is_nullable' => (bool) $f->is_nullable,
            'is_indexed' => (bool) $f->is_indexed,
            'is_system' => (bool) $f->is_system,
            'default_value' => $f->default_value,
            'placeholder' => $f->placeholder,
            'hint' => $f->hint,
            'hint_icon' => $f->hint_icon,
            'width' => $f->width?->value,
            'sort_order' => $f->sort_order,
            'is_hidden_in_form' => (bool) $f->is_hidden_in_form,
            'is_hidden_in_table' => (bool) $f->is_hidden_in_table,
            'is_filterable' => (bool) $f->is_filterable,
            'is_disabled_on_create' => (bool) $f->is_disabled_on_create,
            'is_disabled_on_edit' => (bool) $f->is_disabled_on_edit,
            'is_translatable' => (bool) $f->is_translatable,
            'validation_rules' => $f->validation_rules,
            'settings' => $f->settings ?? [],
            'auto_fill_on' => $f->auto_fill_on,
            'auto_fill_value' => $f->auto_fill_value,
            'options' => $f->relationLoaded('options')
                ? $f->options->map(fn ($o) => $this->fieldOption($o))->all()
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fieldOption(StudioFieldOption $o): array
    {
        return [
            'id' => $o->id,
            'value' => $o->value,
            'label' => $o->label,
            'color' => $o->color,
            'icon' => $o->icon,
            'sort_order' => $o->sort_order,
        ];
    }
}
