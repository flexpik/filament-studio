<?php

use Flexpik\FilamentStudio\Enums\ApiAction;

it('has all five CRUD actions', function () {
    expect(ApiAction::cases())->toHaveCount(5);
    expect(ApiAction::Index->value)->toBe('index');
    expect(ApiAction::Show->value)->toBe('show');
    expect(ApiAction::Store->value)->toBe('store');
    expect(ApiAction::Update->value)->toBe('update');
    expect(ApiAction::Destroy->value)->toBe('destroy');
});

it('returns human-readable labels', function () {
    expect(ApiAction::Index->label())->toBe('List Records');
    expect(ApiAction::Store->label())->toBe('Create Record');
    expect(ApiAction::Update->label())->toBe('Update Record');
    expect(ApiAction::Destroy->label())->toBe('Delete Record');
});

it('returns all as associative array', function () {
    $all = ApiAction::asSelectOptions();
    expect($all)->toBeArray()->toHaveCount(5);
    expect($all['index'])->toBe('List Records');
});
