<?php

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

// Create a concrete test double since AbstractFieldType is abstract
class StubFieldType extends AbstractFieldType
{
    public static string $key = 'stub';

    public static string $label = 'Stub Field';

    public static string $icon = 'heroicon-o-pencil';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'text';

    public static function settingsSchema(): array
    {
        return [];
    }

    public function toFilamentComponent(): Component
    {
        return TextInput::make($this->field->column_name);
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name);
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}

it('can be instantiated with a StudioField', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'test_field',
        'field_type' => 'stub',
        'is_required' => true,
        'width' => 'full',
        'placeholder' => 'Enter value',
        'hint' => 'A helpful hint',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);

    expect($type->field)->toBe($field);
    expect(StubFieldType::$key)->toBe('stub');
    expect(StubFieldType::$label)->toBe('Stub Field');
    expect(StubFieldType::$icon)->toBe('heroicon-o-pencil');
    expect(StubFieldType::$eavCast)->toBe(EavCast::Text);
    expect(StubFieldType::$category)->toBe('text');
});

it('returns a Filament form component from toFilamentComponent', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'my_input',
        'field_type' => 'stub',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Component::class);
});

it('returns a Filament table column from toTableColumn', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'my_col',
        'field_type' => 'stub',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(Column::class);
});

it('returns null from toFilter by default', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'my_field',
        'field_type' => 'stub',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);

    expect($type->toFilter())->toBeNull();
});

it('applies common field properties via applyCommonProperties', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'styled_field',
        'field_type' => 'stub',
        'is_required' => true,
        'placeholder' => 'Type here...',
        'hint' => 'Some help text',
        'is_disabled' => true,
        'width' => 'half',
        'validation_rules' => ['min:3', 'max:100'],
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    expect($component)->toBeInstanceOf(Component::class);
});

it('provides setting helper method', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'test',
        'field_type' => 'stub',
        'settings' => ['prefix' => '$', 'max' => 100],
    ]);

    $type = new StubFieldType($field);

    expect($type->setting('prefix'))->toBe('$');
    expect($type->setting('max'))->toBe(100);
    expect($type->setting('nonexistent'))->toBeNull();
    expect($type->setting('nonexistent', 'fallback'))->toBe('fallback');
});

// --- Boundary-value tests for mutation testing coverage ---

it('applies disabled state on create page context', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'create_disabled',
        'field_type' => 'stub',
        'is_disabled_on_create' => true,
        'is_disabled_on_edit' => false,
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent('create');

    $ref = new ReflectionProperty($component, 'isDisabled');
    expect($ref->getValue($component))->toBeTrue();
});

it('does NOT disable on edit when only create is disabled', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'create_only_disabled',
        'field_type' => 'stub',
        'is_disabled_on_create' => true,
        'is_disabled_on_edit' => false,
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent('edit');

    $ref = new ReflectionProperty($component, 'isDisabled');
    expect($ref->getValue($component))->toBeFalse();
});

it('applies disabled state on edit page context', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'edit_disabled',
        'field_type' => 'stub',
        'is_disabled_on_create' => false,
        'is_disabled_on_edit' => true,
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent('edit');

    $ref = new ReflectionProperty($component, 'isDisabled');
    expect($ref->getValue($component))->toBeTrue();
});

it('does not disable when page context is null', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'both_disabled',
        'field_type' => 'stub',
        'is_disabled_on_create' => true,
        'is_disabled_on_edit' => true,
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent(null);

    $ref = new ReflectionProperty($component, 'isDisabled');
    expect($ref->getValue($component))->toBeFalse();
});

it('applies half width column span as 1', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'half_width',
        'field_type' => 'stub',
        'width' => 'half',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    $span = $component->getColumnSpan();
    expect($span)->toBeArray();
    expect($span['lg'])->toBe(1);
});

it('applies full width column span as 2', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'full_width',
        'field_type' => 'stub',
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    $span = $component->getColumnSpan();
    expect($span)->toBeArray();
    expect($span['lg'])->toBe(2);
});

it('applies expanded width column span as full string', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'expanded_width',
        'field_type' => 'stub',
        'width' => 'expanded',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    $span = $component->getColumnSpan();
    expect($span)->toBeArray();
    expect($span['lg'])->toBe('full');
});

it('marks component as required when is_required is true', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'required_field',
        'field_type' => 'stub',
        'is_required' => true,
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    expect($component->isRequired())->toBeTrue();
});

it('does not mark as required when is_required is false', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'optional_field',
        'field_type' => 'stub',
        'is_required' => false,
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    expect($component->isRequired())->toBeFalse();
});

it('returns null from buildTableColumn when toTableColumn returns null', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'null_column',
        'field_type' => 'stub',
        'width' => 'full',
        'settings' => [],
    ]);

    // Create an anonymous class that returns null from toTableColumn
    $type = new class($field) extends AbstractFieldType
    {
        public static string $key = 'null_col_stub';

        public static string $label = 'Null Column Stub';

        public static string $icon = 'heroicon-o-pencil';

        public static EavCast $eavCast = EavCast::Text;

        public static string $category = 'text';

        public static function settingsSchema(): array
        {
            return [];
        }

        public function toFilamentComponent(): Component
        {
            return TextInput::make($this->field->column_name);
        }

        public function toTableColumn(): ?Column
        {
            return null;
        }

        public function toFilter(): ?BaseFilter
        {
            return null;
        }
    };

    expect($type->buildTableColumn())->toBeNull();
});

it('applies label to table column', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'labeled_col',
        'field_type' => 'stub',
        'label' => 'My Custom Label',
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $column = $type->buildTableColumn();

    expect($column)->not->toBeNull();
    expect($column->getLabel())->toBe('My Custom Label');
});

it('setting returns null for missing key with no default', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'test',
        'field_type' => 'stub',
        'settings' => ['existing' => 'value'],
    ]);

    $type = new StubFieldType($field);

    expect($type->setting('missing_key'))->toBeNull();
});

it('setting returns default for missing key', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'test',
        'field_type' => 'stub',
        'settings' => ['existing' => 'value'],
    ]);

    $type = new StubFieldType($field);

    expect($type->setting('missing_key', 'my_default'))->toBe('my_default');
});

it('setting handles null settings gracefully', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'test',
        'field_type' => 'stub',
        'settings' => null,
    ]);

    $type = new StubFieldType($field);

    expect($type->setting('any_key'))->toBeNull();
    expect($type->setting('any_key', 'fallback'))->toBe('fallback');
});

// --- Tests to kill surviving mutants ---

it('applies placeholder when field has a placeholder value', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'placeholder_field',
        'field_type' => 'stub',
        'placeholder' => 'Enter something',
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    expect($component->getPlaceholder())->toBe('Enter something');
});

it('does not apply placeholder when field placeholder is empty', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'no_placeholder_field',
        'field_type' => 'stub',
        'placeholder' => null,
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    expect($component->getPlaceholder())->toBeNull();
});

it('applies hint when field has a hint value', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'hint_field',
        'field_type' => 'stub',
        'hint' => 'This is helpful',
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    expect($component->getHint())->toBe('This is helpful');
});

it('does not apply hint when field hint is empty', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'no_hint_field',
        'field_type' => 'stub',
        'hint' => null,
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    expect($component->getHint())->toBeNull();
});

it('applies label to form component when field has a label', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'labeled_form_field',
        'field_type' => 'stub',
        'label' => 'Custom Label',
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    expect($component->getLabel())->toBe('Custom Label');
});

it('does not override label when field label is empty', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'no_label_field',
        'field_type' => 'stub',
        'label' => null,
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    // Without explicit label, Filament auto-generates from the column name
    expect($component->getLabel())->not->toBe('Custom Label');
});

it('treats null is_disabled_on_create as false (not disabled)', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'null_create_disabled',
        'field_type' => 'stub',
        'is_disabled_on_create' => null,
        'is_disabled_on_edit' => null,
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent('create');

    $ref = new ReflectionProperty($component, 'isDisabled');
    expect($ref->getValue($component))->toBeFalse();
});

it('treats null is_disabled_on_edit as false (not disabled)', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'null_edit_disabled',
        'field_type' => 'stub',
        'is_disabled_on_create' => null,
        'is_disabled_on_edit' => null,
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent('edit');

    $ref = new ReflectionProperty($component, 'isDisabled');
    expect($ref->getValue($component))->toBeFalse();
});

it('does NOT disable on create when only edit is disabled', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'edit_only_disabled',
        'field_type' => 'stub',
        'is_disabled_on_create' => false,
        'is_disabled_on_edit' => true,
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent('create');

    $ref = new ReflectionProperty($component, 'isDisabled');
    expect($ref->getValue($component))->toBeFalse();
});

it('does not disable when page context is an unknown string', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'unknown_context',
        'field_type' => 'stub',
        'is_disabled_on_create' => true,
        'is_disabled_on_edit' => true,
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent('view');

    $ref = new ReflectionProperty($component, 'isDisabled');
    expect($ref->getValue($component))->toBeFalse();
});

it('applies validation rules when present on the field', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'validated_field',
        'field_type' => 'stub',
        'validation_rules' => ['min:3', 'max:100'],
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    $rules = $component->getValidationRules();
    expect($rules)->toContain('min:3');
    expect($rules)->toContain('max:100');
});

it('does not apply validation rules when validation_rules is empty', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'no_rules_field',
        'field_type' => 'stub',
        'validation_rules' => [],
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    $rules = $component->getValidationRules();
    expect($rules)->not->toContain('min:3');
});

it('does not apply validation rules when validation_rules is null', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'null_rules_field',
        'field_type' => 'stub',
        'validation_rules' => null,
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    $rules = $component->getValidationRules();
    expect($rules)->not->toContain('min:3');
});

it('builds table column with sortable property', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'sortable_col',
        'field_type' => 'stub',
        'label' => 'Sortable Column',
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $column = $type->buildTableColumn();

    expect($column)->not->toBeNull();
    expect($column->isSortable())->toBeTrue();
});

it('builds table column with searchable property', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'searchable_col',
        'field_type' => 'stub',
        'label' => 'Searchable Column',
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $column = $type->buildTableColumn();

    expect($column)->not->toBeNull();
    expect($column->isSearchable())->toBeTrue();
});

it('builds table column with toggleable property', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'toggleable_col',
        'field_type' => 'stub',
        'label' => 'Toggleable Column',
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $column = $type->buildTableColumn();

    expect($column)->not->toBeNull();
    expect($column->isToggleable())->toBeTrue();
});

it('does not apply label to table column when label is empty', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'no_label_col',
        'field_type' => 'stub',
        'label' => null,
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $column = $type->buildTableColumn();

    expect($column)->not->toBeNull();
    // Without explicit label, it should not be a custom string
    expect($column->getLabel())->not->toBe('My Custom Label');
});

it('returns non-null column from buildTableColumn when toTableColumn returns a column', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'real_column',
        'field_type' => 'stub',
        'label' => 'Real Column',
        'width' => 'full',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $column = $type->buildTableColumn();

    expect($column)->toBeInstanceOf(Column::class);
});

it('handles applyValidationRules on component without rules method', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'no_rules_component',
        'field_type' => 'stub',
        'validation_rules' => ['min:3'],
        'width' => 'full',
        'settings' => [],
    ]);

    // Create a stub that returns a component without `rules` method
    $type = new class($field) extends AbstractFieldType
    {
        public static string $key = 'norules_stub';

        public static string $label = 'No Rules Stub';

        public static string $icon = 'heroicon-o-pencil';

        public static EavCast $eavCast = EavCast::Text;

        public static string $category = 'text';

        public static function settingsSchema(): array
        {
            return [];
        }

        public function toFilamentComponent(): Component
        {
            // Use a basic Component subclass that doesn't have a `rules` method
            return Placeholder::make($this->field->column_name);
        }

        public function toTableColumn(): ?Column
        {
            return null;
        }

        public function toFilter(): ?BaseFilter
        {
            return null;
        }
    };

    // Should not throw - the applyValidationRules should early return
    $component = $type->buildFormComponent();
    expect($component)->toBeInstanceOf(Component::class);
});

it('applies applyColumnSpan via buildFormComponent', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'span_test',
        'field_type' => 'stub',
        'width' => 'half',
        'settings' => [],
    ]);

    $type = new StubFieldType($field);
    $component = $type->buildFormComponent();

    // Verify applyColumnSpan was called (RemoveMethodCall on line 121)
    $span = $component->getColumnSpan();
    expect($span)->toBeArray();
    expect($span['lg'])->toBe(1);
});
