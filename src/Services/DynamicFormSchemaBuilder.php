<?php

namespace Flexpik\FilamentStudio\Services;

use Filament\Schemas\Components\Section;
use Flexpik\FilamentStudio\FieldTypes\FieldTypeRegistry;
use Flexpik\FilamentStudio\FieldTypes\Types\SectionHeaderFieldType;
use Flexpik\FilamentStudio\FilamentStudioPlugin;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User;

class DynamicFormSchemaBuilder
{
    /**
     * Build a Filament form schema array from a collection's fields.
     *
     * @return array<Section>
     */
    public static function build(
        StudioCollection $collection,
        ?string $pageContext = null,
        ?User $user = null,
    ): array {
        /** @var Collection<int, StudioField> $fields */
        $fields = $collection->fields()
            ->where('is_hidden_in_form', false)
            ->orderBy('is_system')
            ->orderBy('sort_order')
            ->get();

        if ($fields->isEmpty()) {
            return [];
        }

        $registry = app(FieldTypeRegistry::class);

        $triggerFieldNames = ConditionEvaluator::collectTriggerFields($fields);

        $sections = [];
        $currentSectionField = null;
        $currentFields = [];

        foreach ($fields as $field) {
            if ($field->field_type === SectionHeaderFieldType::$key) {
                if ($currentSectionField !== null || count($currentFields) > 0) {
                    $sections[] = static::makeSection($currentSectionField, $currentFields, $registry, $triggerFieldNames, $pageContext, $user);
                    $currentFields = [];
                }
                $currentSectionField = $field;
            } else {
                $currentFields[] = $field;
            }
        }

        if (count($currentFields) > 0) {
            $sections[] = static::makeSection($currentSectionField, $currentFields, $registry, $triggerFieldNames, $pageContext, $user);
        }

        $sections = FilamentStudioPlugin::applyModifyFormSchema($sections, $collection);

        return $sections;
    }

    /**
     * Build a Section component from a header field and its child fields.
     *
     * @param  array<StudioField>  $fields
     * @param  string[]  $triggerFieldNames
     */
    protected static function makeSection(
        ?StudioField $headerField,
        array $fields,
        FieldTypeRegistry $registry,
        array $triggerFieldNames,
        ?string $pageContext,
        ?User $user,
    ): Section {
        $settings = $headerField !== null ? ($headerField->settings ?? []) : [];

        $section = Section::make($settings['section_label'] ?? null)
            ->description($settings['description'] ?? null)
            ->icon($settings['icon'] ?? null)
            ->columns((int) ($settings['columns'] ?? 1));

        if ($settings['collapsible'] ?? false) {
            $section->collapsible();
        }

        if ($settings['collapsed'] ?? false) {
            $section->collapsed();
        }

        $schema = collect($fields)
            ->map(function (StudioField $f) use ($registry, $triggerFieldNames, $pageContext, $user) {
                $component = $registry->make($f)->buildFormComponent($pageContext);

                $conditions = $f->settings['conditions'] ?? null;
                if ($conditions) {
                    $evaluator = new ConditionEvaluator($conditions, $pageContext, $user);

                    if ($evaluator->hasVisible()) {
                        $component->visible($evaluator->buildVisibleClosure());
                        $component->dehydrated($evaluator->buildDehydratedClosure());
                    }
                    if ($evaluator->hasRequired() && method_exists($component, 'required')) {
                        $component->required($evaluator->buildRequiredClosure());

                        if (method_exists($component, 'rules')) {
                            $rules = $f->validation_rules ?? [];
                            $rules = array_filter($rules, fn ($r) => $r !== 'required');
                            if (! empty($rules)) {
                                $component->rules($rules);
                            }
                        }
                    }
                    if ($evaluator->hasDisabled()) {
                        $component->disabled($evaluator->buildDisabledClosure());
                    }
                }

                if (in_array($f->column_name, $triggerFieldNames)) {
                    $component->live();
                }

                return $component;
            })
            ->all();

        $section->schema($schema);

        return $section;
    }
}
