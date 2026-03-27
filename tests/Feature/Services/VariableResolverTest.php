<?php

use Filament\Facades\Filament;
use Flexpik\FilamentStudio\Services\VariableResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

it('resolves $CURRENT_RECORD to the given record uuid', function () {
    $resolver = new VariableResolver;

    $result = $resolver->resolve('$CURRENT_RECORD', [], 'abc-123');

    expect($result)->toBe('abc-123');
});

it('resolves $CURRENT_RECORD to null when no record uuid given', function () {
    $resolver = new VariableResolver;

    $result = $resolver->resolve('$CURRENT_RECORD', [], null);

    expect($result)->toBeNull();
});

it('resolves $NOW to current datetime string', function () {
    Carbon::setTestNow('2026-03-14 12:00:00');
    $resolver = new VariableResolver;

    $result = $resolver->resolve('$NOW', [], null);

    expect($result)->toBe('2026-03-14 12:00:00');
    Carbon::setTestNow();
});

it('resolves $NOW with adjustment', function () {
    Carbon::setTestNow('2026-03-14 12:00:00');
    $resolver = new VariableResolver;

    $result = $resolver->resolve('$NOW(-7 days)', [], null);

    expect($result)->toBe('2026-03-07 12:00:00');
    Carbon::setTestNow();
});

it('resolves $NOW with positive adjustment', function () {
    Carbon::setTestNow('2026-03-14 12:00:00');
    $resolver = new VariableResolver;

    $result = $resolver->resolve('$NOW(+30 days)', [], null);

    expect($result)->toBe('2026-04-13 12:00:00');
    Carbon::setTestNow();
});

it('resolves variable token from variables array', function () {
    $resolver = new VariableResolver;

    $result = $resolver->resolve('{{date_start}}', ['date_start' => '2026-01-01'], null);

    expect($result)->toBe('2026-01-01');
});

it('returns null for unresolved variable token', function () {
    $resolver = new VariableResolver;

    $result = $resolver->resolve('{{unknown_var}}', [], null);

    expect($result)->toBeNull();
});

it('passes through non-variable strings unchanged', function () {
    $resolver = new VariableResolver;

    $result = $resolver->resolve('plain string', [], null);

    expect($result)->toBe('plain string');
});

it('passes through non-string values unchanged', function () {
    $resolver = new VariableResolver;

    expect($resolver->resolve(42, [], null))->toBe(42);
    expect($resolver->resolve(true, [], null))->toBeTrue();
    expect($resolver->resolve(null, [], null))->toBeNull();
});

it('resolves all variable tokens in a filter tree recursively', function () {
    $resolver = new VariableResolver;

    $filterTree = [
        'logic' => 'and',
        'rules' => [
            ['field' => 'date', 'operator' => 'gte', 'value' => '{{start_date}}'],
            ['field' => 'owner', 'operator' => 'eq', 'value' => '$CURRENT_RECORD'],
        ],
    ];

    $resolved = $resolver->resolveTree($filterTree, ['start_date' => '2026-01-01'], 'rec-uuid');

    expect($resolved['rules'][0]['value'])->toBe('2026-01-01')
        ->and($resolved['rules'][1]['value'])->toBe('rec-uuid');
});

it('resolves variable token with extra whitespace', function () {
    $resolver = new VariableResolver;

    $result = $resolver->resolve('{{ date_start }}', ['date_start' => '2026-06-01'], null);

    expect($result)->toBe('2026-06-01');
});

it('returns non-string values unchanged without resolving', function () {
    $resolver = new VariableResolver;

    // Array should be returned as-is (not walked into)
    $result = $resolver->resolve(['nested' => 'value'], [], null);
    expect($result)->toBe(['nested' => 'value']);

    // Float should be returned as-is
    $result = $resolver->resolve(3.14, [], null);
    expect($result)->toBe(3.14);
});

it('resolves $CURRENT_TENANT to tenant key', function () {
    $resolver = new VariableResolver;
    $tenant = Mockery::mock(Model::class);
    $tenant->shouldReceive('getKey')->andReturn(99);
    Filament::shouldReceive('getTenant')->andReturn($tenant);

    $result = $resolver->resolve('$CURRENT_TENANT', [], null);
    expect($result)->toBe(99);
});

it('resolves $CURRENT_TENANT to null when no tenant', function () {
    $resolver = new VariableResolver;
    Filament::shouldReceive('getTenant')->andReturn(null);

    $result = $resolver->resolve('$CURRENT_TENANT', [], null);
    expect($result)->toBeNull();
});

it('does not resolve string starting with {{ but not ending with }}', function () {
    $resolver = new VariableResolver;

    $result = $resolver->resolve('{{not_closed', ['not_closed' => 'value'], null);

    expect($result)->toBe('{{not_closed');
});

it('does not resolve string ending with }} but not starting with {{', function () {
    $resolver = new VariableResolver;

    $result = $resolver->resolve('not_opened}}', ['not_opened' => 'value'], null);

    expect($result)->toBe('not_opened}}');
});

it('correctly extracts inner expression from $NOW adjustment', function () {
    Carbon::setTestNow('2026-03-14 12:00:00');
    $resolver = new VariableResolver;

    // $NOW(+1 hour) - substr($value, 5, -1) should extract "+1 hour"
    $result = $resolver->resolve('$NOW(+1 hour)', [], null);

    expect($result)->toBe('2026-03-14 13:00:00');
    Carbon::setTestNow();
});

it('resolves tree with nested groups containing $NOW and $CURRENT_RECORD tokens', function () {
    Carbon::setTestNow('2026-03-14 12:00:00');
    $resolver = new VariableResolver;

    $tree = [
        'logic' => 'and',
        'rules' => [
            ['field' => 'created_at', 'operator' => 'gte', 'value' => '$NOW'],
            [
                'logic' => 'or',
                'rules' => [
                    ['field' => 'owner', 'operator' => 'eq', 'value' => '$CURRENT_RECORD'],
                    [
                        'logic' => 'and',
                        'rules' => [
                            ['field' => 'due', 'operator' => 'lte', 'value' => '$NOW(+7 days)'],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $resolved = $resolver->resolveTree($tree, [], 'rec-nested-uuid');

    expect($resolved['rules'][0]['value'])->toBe('2026-03-14 12:00:00')
        ->and($resolved['rules'][1]['rules'][0]['value'])->toBe('rec-nested-uuid')
        ->and($resolved['rules'][1]['rules'][1]['rules'][0]['value'])->toBe('2026-03-21 12:00:00');

    Carbon::setTestNow();
});
