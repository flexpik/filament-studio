<?php

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;
use Flexpik\FilamentStudio\FieldTypes\FieldTypeRegistry;
use Flexpik\FilamentStudio\Models\StudioField;

// Stub types for testing registration
class RegistryStubAlpha extends AbstractFieldType
{
    public static string $key = 'registry_alpha';

    public static string $label = 'Alpha';

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

class RegistryStubBeta extends AbstractFieldType
{
    public static string $key = 'registry_beta';

    public static string $label = 'Beta';

    public static string $icon = 'heroicon-o-calculator';

    public static EavCast $eavCast = EavCast::Integer;

    public static string $category = 'numeric';

    public static function settingsSchema(): array
    {
        return [];
    }

    public function toFilamentComponent(): Component
    {
        return TextInput::make($this->field->column_name)->numeric();
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name)->numeric();
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}

beforeEach(function () {
    $this->registry = new FieldTypeRegistry;
});

it('can register a field type class', function () {
    $this->registry->register(RegistryStubAlpha::class);

    expect($this->registry->all())->toHaveKey('registry_alpha');
    expect($this->registry->all()['registry_alpha'])->toBe(RegistryStubAlpha::class);
});

it('can register multiple field types', function () {
    $this->registry->register(RegistryStubAlpha::class);
    $this->registry->register(RegistryStubBeta::class);

    expect($this->registry->all())->toHaveCount(2);
    expect($this->registry->all())->toHaveKeys(['registry_alpha', 'registry_beta']);
});

it('can make an instance from a StudioField', function () {
    $this->registry->register(RegistryStubAlpha::class);

    $field = StudioField::factory()->make([
        'column_name' => 'name',
        'field_type' => 'registry_alpha',
        'settings' => [],
    ]);

    $instance = $this->registry->make($field);

    expect($instance)->toBeInstanceOf(RegistryStubAlpha::class);
    expect($instance->field)->toBe($field);
});

it('throws exception when making unregistered type', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'unknown',
        'field_type' => 'nonexistent_type',
        'settings' => [],
    ]);

    $this->registry->make($field);
})->throws(InvalidArgumentException::class);

it('returns types grouped by category', function () {
    $this->registry->register(RegistryStubAlpha::class);
    $this->registry->register(RegistryStubBeta::class);

    $categories = $this->registry->categories();

    expect($categories)->toHaveKey('text');
    expect($categories)->toHaveKey('numeric');
    expect($categories['text'])->toHaveCount(1);
    expect($categories['numeric'])->toHaveCount(1);
    expect($categories['text'][0])->toBe(RegistryStubAlpha::class);
});

it('can register and invoke a custom value transformer', function () {
    $this->registry->registerTransformer(
        'registry_alpha',
        serialize: fn ($value) => strtoupper($value),
        deserialize: fn ($raw) => strtolower($raw),
    );

    $transformer = $this->registry->getTransformer('registry_alpha');

    expect($transformer)->not->toBeNull();
    expect(($transformer['serialize'])('hello'))->toBe('HELLO');
    expect(($transformer['deserialize'])('HELLO'))->toBe('hello');
});

it('returns null transformer for unregistered type', function () {
    expect($this->registry->getTransformer('no_such_type'))->toBeNull();
});

// --- Boundary-value tests for mutation testing coverage ---

it('overwrites a registration with same key', function () {
    $this->registry->register(RegistryStubAlpha::class);
    $this->registry->register(RegistryStubAlpha::class);

    expect($this->registry->all())->toHaveCount(1);
    expect($this->registry->all()['registry_alpha'])->toBe(RegistryStubAlpha::class);
});

it('returns empty array when no types registered', function () {
    expect($this->registry->all())->toBe([]);
    expect($this->registry->all())->toHaveCount(0);
});

it('returns empty categories when no types registered', function () {
    expect($this->registry->categories())->toBe([]);
    expect($this->registry->categories())->toHaveCount(0);
});

it('overwrites transformer when registered twice for same key', function () {
    $this->registry->registerTransformer(
        'registry_alpha',
        serialize: fn ($value) => strtoupper($value),
        deserialize: fn ($raw) => strtolower($raw),
    );

    $this->registry->registerTransformer(
        'registry_alpha',
        serialize: fn ($value) => strrev($value),
        deserialize: fn ($raw) => strrev($raw),
    );

    $transformer = $this->registry->getTransformer('registry_alpha');

    expect($transformer)->not->toBeNull();
    expect(($transformer['serialize'])('hello'))->toBe('olleh');
    expect(($transformer['deserialize'])('olleh'))->toBe('hello');
});
